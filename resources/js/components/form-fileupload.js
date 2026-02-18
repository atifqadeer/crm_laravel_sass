/*
Template Name: Lahomes - Real Estate Admin Dashboard Template
Author: Techzaa
File: form - Dropzone js
*/
import Dropzone from 'dropzone/dist/dropzone';

Dropzone.autoDiscover = false;

document.addEventListener("DOMContentLoaded", function () {

    const dropzoneElement = document.querySelector("#applicantCvDropzone");
    if (!dropzoneElement) return;

    const templateNode = document.querySelector("#dz-preview-template");
    if (!templateNode) return;

    const previewTemplate = templateNode.innerHTML;

    new Dropzone("#applicantCvDropzone", {
        url: "https://httpbin.org/post",
        method: "post",
        previewsContainer: "#dropzone-preview",
        previewTemplate: previewTemplate,
        acceptedFiles: ".pdf,.doc,.docx,.csv",
        maxFilesize: 10,       // max file size in MB
        maxFiles: 1,           // allow only 1 file
        clickable: true,       // allows clicking to select file

        init: function () {
            this.on("addedfile", function (file) {
                const ext = file.name.split('.').pop().toLowerCase();
                const thumbnailContainer = file.previewElement.querySelector("[data-dz-thumbnail]");

                if (!thumbnailContainer) return;

                let icon = "solar:file-bold";   // default icon
                let colorClass = "text-secondary"; // default color

                if (ext === "pdf") {
                    icon = "solar:file-text-bold";
                    colorClass = "text-danger"; // red
                } 
                else if (ext === "doc" || ext === "docx") {
                    icon = "solar:document-bold";
                    colorClass = "text-primary"; // blue
                } 
                else if (ext === "csv") {
                    icon = "solar:table-bold";
                    colorClass = "text-success"; // green
                }

                // Inject icon with color dynamically
                thumbnailContainer.innerHTML = `<iconify-icon icon="${icon}" class="fs-32 ${colorClass}"></iconify-icon>`;
            });


            // Optional: Remove previous file if user adds a new one
            this.on("maxfilesexceeded", function(file) {
                this.removeAllFiles(); // remove previous file
                this.addFile(file);    // add new one
            });
        }
    });
});
