/*
 * Copyright (c) 2023. Thomas Schulte <thomas@cupracer.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute,
 * sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

import Quagga from '@ericblade/quagga2/dist/quagga';

// make Quagga available globally
// window.Quagga = Quagga;

window.addBarcodeListenerAndUpdateSelect2field =

    function addBarcodeListenerAndUpdateSelect2field(elementId, select2field) {
        // Event listener for the image upload
        document.getElementById(elementId).addEventListener("change", function(e) {
            let fileInputElement = e.target;
            // console.log("Image input changed: " + fileInputElement.id);

            // get the chosen file
            if(fileInputElement.files.length !== 1 || fileInputElement.files[0].type.indexOf("image/") !== 0) {
                console.log("No image file selected");
                return;
            }

            let file = fileInputElement.files[0];

            // create a URL for the image
            let imgUrl = URL.createObjectURL(file);

            // create an image object
            let img = new Image();
            img.onload = function() {
                // console.log("Image loaded. Processing...");
                processImage(imgUrl);
            };

            // load the image to trigger the onload event
            img.src = imgUrl;

            // reset the input element to allow the same file to be selected again
            fileInputElement.value = '';
        });

        // Process the provided image with Quagga
        function processImage(img) {
            // console.log("Image processing started...");
            Quagga.decodeSingle({
                decoder: { readers: ["ean_8_reader", "ean_reader"] },
                locate: true,
                src: img
            }, function(result) {
                if(result && result.codeResult) {
                    // console.log("Barcode detected and processed : [" + result.codeResult.code + "]", result);
                    // write the barcode to the selected element
                    // productSelect.select2('open');
                    select2field.data('select2').dropdown.$search.val(result.codeResult.code).trigger('input');

                    // productSelect.val(result.codeResult.code).trigger('change');
                    // console.log("Select2 value set to " + result.codeResult.code + ". Triggered change event.");
                } else {
                    console.log("Barcode could not be detected");
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        // TODO: make body string translatable
                        // body: "{{ 'app.supplies.article.barcode.notfound'|trans }}",
                        body: "Barcode could not be detected",
                        autohide: true,
                        delay: 4000,
                        icon: 'fas fa-exclamation fa-lg',
                    });
                }
            });
        }
    }