document.addEventListener("DOMContentLoaded", function () {
    let frameThumbnails = document.getElementById("gm-frame-thumbnails");

    new Sortable(frameThumbnails, {
        animation: 150, // Animasi saat drag
        ghostClass: "sortable-ghost", // Class untuk efek transparan saat drag
        onEnd: function (evt) {
            console.log("Frames reordered!", evt);
        }
    });
});