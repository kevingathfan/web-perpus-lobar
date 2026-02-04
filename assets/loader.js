(function () {
    function hideLoader() {
        var loader = document.getElementById("global-loader");
        if (!loader) {
            return;
        }

        setTimeout(function () {
            loader.classList.add("loader-hidden");
            loader.addEventListener("transitionend", function () {
                if (loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            });
        }, 500);
    }

    if (document.readyState === "complete") {
        hideLoader();
    } else {
        window.addEventListener("load", hideLoader);
    }
})();
