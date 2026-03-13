(function () {
    function hideLoader() {
        var loader = document.getElementById("global-loader");
        if (!loader || loader.classList.contains("loader-hidden")) {
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

    // Fallback: Force remove/hide after 3 seconds if load event is stuck
    setTimeout(hideLoader, 3000);

    if (document.readyState === "complete" || document.readyState === "interactive") {
        hideLoader();
    } else {
        document.addEventListener("DOMContentLoaded", hideLoader);
        window.addEventListener("load", hideLoader);
    }
})();
