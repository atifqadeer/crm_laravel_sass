// ─────────────────────────────────────────────────────────────────────────────
// sip-engine.jsx  — JsSIP WebRTC SIP engine for xplosip / FreePBX
//
// Exposes:  window.XplosipSIP  (singleton — safe to call before UA is ready)
//
// Requires: JsSIP loaded BEFORE this script via <script src="...jssip...">
// ─────────────────────────────────────────────────────────────────────────────

(function buildSipEngine() {
  'use strict';

  // ── Remote audio element (created once, lives forever) ──────────────────────
  let _remoteAudio = null;
  function getRemoteAudio() {
    if (!_remoteAudio) {
      _remoteAudio = document.createElement('audio');
      _remoteAudio.id = 'xplosip-remote-audio';
      _remoteAudio.autoplay = true;
      _remoteAudio.style.display = 'none';
      document.body.appendChild(_remoteAudio);
    }
    return _remoteAudio;
  }

  // ── Attach remote stream to audio element ────────────────────────────────────
  function attachRemoteStream(session) {
    const audio = getRemoteAudio();
    const pc = session.connection;
    if (!pc) return;

    // Modern track-based API (Chrome 70+, Firefox, Safari)
    pc.ontrack = function (e) {
      if (e.streams && e.streams[0]) {
        audio.srcObject = e.streams[0];
      }
    };

    // Legacy stream-based API fallback
    pc.onaddstream = function (e) {
      if (e.stream) audio.srcObject = e.stream;
    };

    // If tracks already exist (race: event fired before handler attached)
    const receivers = pc.getReceivers ? pc.getReceivers() : [];
    if (receivers.length > 0) {
      const stream = new MediaStream(receivers.map(r => r.track).filter(Boolean));
      if (stream.getTracks().length) audio.srcObject = stream;
    }
  }

  // ── State ────────────────────────────────────────────────────────────────────
  let _ua            = null;
  let _currentSession = null;
  let _callbacks     = {};
  let _config        = null;

  // ── Internal helpers ─────────────────────────────────────────────────────────
  function _notify(event, ...args) {
    try { if (_callbacks[event]) _callbacks[event](...args); } catch (e) {
      console.error('[xplosip] callback error', event, e);
    }
  }

  function _wireSessionEvents(session, direction) {
    _currentSession = session;

    session.on('progress', function (e) {
      // 1xx received — still ringing
      _notify('callState', direction === 'out' ? 'ringing-out' : 'ringing-in', null, session);
    });

    session.on('accepted', function (e) {
      attachRemoteStream(session);
      _notify('callState', 'connected', null, session);
    });

    session.on('confirmed', function (e) {
      // ACK sent/received — call fully established
      attachRemoteStream(session);
      _notify('callState', 'connected', null, session);
    });

    session.on('failed', function (e) {
      _currentSession = null;
      _notify('callState', 'ended', e.cause || 'Failed', null);
    });

    session.on('ended', function (e) {
      _currentSession = null;
      _notify('callState', 'ended', e.cause || 'Normal', null);
    });

    session.on('hold', function (e) {
      _notify('holdChange', true, e.originator);
    });

    session.on('unhold', function (e) {
      _notify('holdChange', false, e.originator);
    });

    session.on('muted', function (e) {
      _notify('muteChange', true);
    });

    session.on('unmuted', function (e) {
      _notify('muteChange', false);
    });
  }

  // ── Public API ───────────────────────────────────────────────────────────────
  const XplosipSIP = {

    /**
     * init(config, callbacks)
     *
     * config:
     *   wsUri        — WebSocket URL,  e.g. 'wss://voip.ibstec.com:8089/ws'
     *   domain       — SIP domain,      e.g. 'voip.ibstec.com'
     *   extension    — SIP username,    e.g. '1001'
     *   password     — SIP password
     *   displayName  — caller ID name   (optional)
     *   stun         — STUN server URI  (optional, defaults to Google)
     *
     * callbacks:
     *   onRegistered(status)           — 'registered' | 'unregistered'
     *   onRegistrationFailed(cause)
     *   onIncomingCall(number, session)
     *   onCallState(state, cause, session) — 'ringing-out'|'ringing-in'|'connected'|'ended'
     *   onHoldChange(held, originator)
     *   onMuteChange(muted)
     */
    init: function (config, callbacks) {
      _callbacks = callbacks || {};
      _config    = config;

      // Tear down any previous UA
      if (_ua) {
        try { _ua.stop(); } catch (_) {}
        _ua = null;
      }

      if (!window.JsSIP) {
        console.error('[xplosip] JsSIP library not loaded — cannot initialise SIP UA');
        return;
      }

      // Suppress JsSIP's verbose debug logging
      JsSIP.debug.disable('JsSIP:*');

      const socket = new JsSIP.WebSocketInterface(config.wsUri);

      _ua = new JsSIP.UA({
        sockets:       [socket],
        uri:           'sip:' + config.extension + '@' + config.domain,
        password:      config.password,
        display_name:  config.displayName || String(config.extension),
        register:      true,
        session_timers: false,
        use_preloaded_route: false,
      });

      // ── Registration events ──────────────────────────────────────────────────
      _ua.on('registered', function () {
        _notify('onRegistered', 'registered');
      });

      _ua.on('unregistered', function () {
        _notify('onRegistered', 'unregistered');
      });

      _ua.on('registrationFailed', function (e) {
        console.warn('[xplosip] Registration failed:', e.cause);
        _notify('onRegistrationFailed', e.cause);
      });

      // ── Incoming call ────────────────────────────────────────────────────────
      _ua.on('newRTCSession', function (data) {
        const session    = data.session;
        const originator = data.originator; // 'local' | 'remote'

        if (originator === 'remote') {
          // Incoming call
          const callerNumber = session.remote_identity
            ? (session.remote_identity.uri.user || session.remote_identity.uri.toString())
            : 'Unknown';

          _wireSessionEvents(session, 'in');
          _notify('onIncomingCall', callerNumber, session);
        }
        // Outgoing sessions are wired in call() below
      });

      _ua.on('connected', function () {
        console.log('[xplosip] WebSocket connected');
      });

      _ua.on('disconnected', function () {
        console.warn('[xplosip] WebSocket disconnected — will retry');
      });

      _ua.start();
    },

    /**
     * call(number)   — place an outbound call
     * Returns the JsSIP RTCSession (or null on error).
     */
    call: function (number) {
      if (!_ua || !_config) {
        console.error('[xplosip] SIP not initialized — call() ignored');
        return null;
      }

      const target = 'sip:' + number + '@' + _config.domain;
      const stunUri = _config.stun || 'stun:stun.l.google.com:19302';

      const options = {
        mediaConstraints:    { audio: true, video: false },
        rtcOfferConstraints: { offerToReceiveAudio: true, offerToReceiveVideo: false },
        pcConfig: {
          iceServers: [{ urls: stunUri }],
        },
      };

      let session;
      try {
        session = _ua.call(target, options);
      } catch (err) {
        console.error('[xplosip] call() error:', err);
        _notify('callState', 'ended', err.message, null);
        return null;
      }

      _wireSessionEvents(session, 'out');
      return session;
    },

    /**
     * answer(session)  — answer an incoming call
     */
    answer: function (session) {
      const s = session || _currentSession;
      if (!s) return;
      const stunUri = (_config && _config.stun) || 'stun:stun.l.google.com:19302';
      s.answer({
        mediaConstraints: { audio: true, video: false },
        pcConfig: { iceServers: [{ urls: stunUri }] },
      });
      attachRemoteStream(s);
    },

    /**
     * hangup(session)  — terminate any call state (ringing, connected, etc.)
     */
    hangup: function (session) {
      const s = session || _currentSession;
      if (!s) return;
      try {
        s.terminate();
      } catch (e) {
        // Already terminated — ignore
      }
      _currentSession = null;
    },

    /**
     * sendDTMF(digit, session)  — send a DTMF tone in-call
     */
    sendDTMF: function (digit, session) {
      const s = session || _currentSession;
      if (!s) return;
      try { s.sendDTMF(digit, { duration: 100, interToneGap: 70 }); } catch (_) {}
    },

    /**
     * toggleMute(session)  — toggle microphone mute; returns new muted state
     */
    toggleMute: function (session) {
      const s = session || _currentSession;
      if (!s || !s.isEstablished()) return false;
      const isMuted = s.isMuted().audio;
      if (isMuted) {
        s.unmute({ audio: true });
        return false;
      } else {
        s.mute({ audio: true });
        return true;
      }
    },

    /**
     * toggleHold(session)  — toggle call hold; returns new held state
     */
    toggleHold: function (session) {
      const s = session || _currentSession;
      if (!s || !s.isEstablished()) return false;
      const isHeld = s.isOnHold().local;
      if (isHeld) {
        s.unhold();
        return false;
      } else {
        s.hold();
        return true;
      }
    },

    /** Is the UA currently registered? */
    isRegistered: function () {
      return _ua ? _ua.isRegistered() : false;
    },

    /** Stop and clean up the UA */
    stop: function () {
      if (_ua) {
        try { _ua.stop(); } catch (_) {}
        _ua = null;
      }
      _currentSession = null;
      _callbacks = {};
      _config    = null;
    },
  };

  window.XplosipSIP = XplosipSIP;

})();
