(function () {
    "use strict";

    var loader = document.getElementById("loader");
    if (!loader) return;

    function hideLoader() {
        loader.classList.add("hide");
        loader.setAttribute("aria-hidden", "true");
        document.body.removeAttribute("aria-busy");
    }

    function showLoader() {
        loader.classList.remove("hide");
        loader.setAttribute("aria-hidden", "false");
        document.body.setAttribute("aria-busy", "true");
    }

    function shouldSkipLink(link, event) {
        if (event.defaultPrevented || event.button !== 0 ||
            event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return true;

        if (link.hasAttribute("download") || (link.target && link.target !== "_self") ||
            link.hasAttribute("data-no-loader") || link.getAttribute("rel") === "external") return true;

        var href = link.getAttribute("href") || "";
        if (!href || href.charAt(0) === "#" || /^(mailto:|tel:|javascript:)/i.test(href)) return true;

        var destination;
        try {
            destination = new URL(link.href, window.location.href);
        } catch (error) {
            return true;
        }

        if (destination.origin !== window.location.origin) return true;
        if (destination.href.split("#")[0] === window.location.href.split("#")[0]) return true;
        if (/\.(pdf|csv|xls|xlsx|zip)$/i.test(destination.pathname)) return true;
        return /(^|\/)download(?:_[^/]*)?\.php$/i.test(destination.pathname);
    }

    function formIsDownload(form) {
        var action;
        try {
            action = new URL(form.action || window.location.href, window.location.href);
        } catch (error) {
            return false;
        }

        return /(^|\/)download(?:_[^/]*)?\.php$/i.test(action.pathname) ||
            /\.(pdf|csv|xls|xlsx|zip)$/i.test(action.pathname);
    }

    window.addEventListener("load", function () {
        window.setTimeout(hideLoader, 120);
    }, { once: true });

    window.addEventListener("pageshow", function (event) {
        if (event.persisted) hideLoader();
    });

    document.addEventListener("click", function (event) {
        var target = event.target;
        var link = target && target.closest ? target.closest("a[href]") : null;
        if (link && !shouldSkipLink(link, event)) showLoader();
    });

    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!form || form.tagName !== "FORM" || event.defaultPrevented ||
            form.hasAttribute("data-no-loader") ||
            (form.target && form.target !== "_self") || formIsDownload(form)) return;

        showLoader();
    });
}());