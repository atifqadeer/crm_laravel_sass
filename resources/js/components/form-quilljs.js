/*
Template Name: Ubold - Responsive Bootstrap 5 Admin Dashboard
Author: Techzaa
File: Quilljs init js
*/
import Quill from 'quill'

// Snow theme
var ele = document.getElementById('snow-editor')
if (ele) {
    var quill = new Quill(ele, {
        theme: 'snow',
        modules: {
            'toolbar': [[{'font': []}, {'size': []}], ['bold', 'italic', 'underline', 'strike'], [{'color': []}, {'background': []}], [{'script': 'super'}, {'script': 'sub'}], [{'header': [false, 1, 2, 3, 4, 5, 6]}, 'blockquote', 'code-block'], [{'list': 'ordered'}, {'list': 'bullet'}, {'indent': '-1'}, {'indent': '+1'}], ['direction', {'align': []}], ['link', 'image', 'video'], ['clean']]
        },
    });

}

// Bubble theme
var ele = document.getElementById('bubble-editor')
if (ele) {
    var quill = new Quill(ele, {
        theme: 'bubble'
    });
}
