//https://github.com/taniarascia/upload

const url = 'process.php';
const form = document.querySelector('form');
const button = document.getElementById('sendFiles');

button.addEventListener('click', e => {
    e.preventDefault();

    const files = document.querySelector('[type=file]').files;
    const formData = new FormData();

    for (let i = 0; i < files.length; i++) {
        let file = files[i];

        formData.append('files[]', file);
    }

    fetch(url, {
        method: 'POST',
        body: formData
    }).then(response => {
        return response.text();
    }).then(data => {
        console.log(data);
    });
});