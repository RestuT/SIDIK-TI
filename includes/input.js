    function updateFileName() {
        const input = document.getElementById('file-upload');
        const display = document.getElementById('file-name-display');
        const label = document.getElementById('file-label');
        const dropzone = document.getElementById('dropzone');

        if (input.files.length > 0) {
            display.innerText = "Terpilih: " + input.files[0].name;
            display.classList.remove('hidden');
            label.classList.add('hidden');
            dropzone.classList.add('border-blue-500', 'bg-blue-50');
        }
    }

