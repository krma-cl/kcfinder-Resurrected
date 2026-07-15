/*! jQuery adapter for KCFinder
 */
/*  BASE USAGE:
 *     <div id="filemanager" style="width:700px;height:400px"></div>
 *     <script>
 *         $('#filemanager').kcfinder();
 *     </script>
 */

(function($) {
    var defaultURL = "browse.php"; // Define here your default URL to KCFinder

    $.fn.kcfinder = function(options) {

        var url, i,
            t = $(this).get(0),

            // Default options
            o = {
                url: defaultURL,
                lang: "",
                theme: "",
                type: "",
                dir: "",
                callback: false,
                callbackMultiple: false,
                selector: false,
                selectorOrigin: "",
                selectorMultiple: false,
                callbackObject: false,
                callbackMultipleObjects: false
            },
            ifr = $('<iframe></iframe>'),

            // GET parameters to parse URL
            parse = ['lang', 'theme', 'type', 'dir'];

        $.extend(true, o, options);

        // Parse URL
        url = o.url;
        url += (url.indexOf('?') === -1) ? '?' : "&";
        for (i in parse) {
            i = parse[i];
            if (o[i].length)
                url += i + "=" + encodeURIComponent(o[i]) + "&";
        }
        if (o.selector || $.isFunction(o.callbackObject) || $.isFunction(o.callbackMultipleObjects)) {
            url += "selector=1&";
            if (o.selectorMultiple || $.isFunction(o.callbackMultipleObjects))
                url += "selectorMultiple=1&";
            if (typeof o.selectorOrigin === "string" && o.selectorOrigin.length)
                url += "selectorOrigin=" + encodeURIComponent(o.selectorOrigin) + "&";
        }
        url = url.substring(0, url.length - 1);

        // Iframe setup
        ifr.css({
            margin: 0,
            padding: 0,
            width: $(t).innerWidth(),
            height: $(t).innerHeight(),
            border: "none"
        }).attr({
            src: url
        });

        $(t).html(ifr);

        // Callbacks
        if ($.isFunction(o.callback) || $.isFunction(o.callbackMultiple) ||
            $.isFunction(o.callbackObject) || $.isFunction(o.callbackMultipleObjects)) {
            if (!window.KCFinder)
                window.KCFinder = {};

            // Single file callback
            if ($.isFunction(o.callback))
                window.KCFinder.callBack = o.callback;
            else if (window.KCFinder && window.KCFinder.callBack)
                delete window.KCFinder.callBack;

            // Multiple files callback
            if ($.isFunction(o.callbackMultiple))
                window.KCFinder.callBackMultiple = o.callbackMultiple;
            else if (window.KCFinder && window.KCFinder.callBackMultiple)
                delete window.KCFinder.callBackMultiple;

            if ($.isFunction(o.callbackObject))
                window.KCFinder.callBackObject = o.callbackObject;
            else if (window.KCFinder && window.KCFinder.callBackObject)
                delete window.KCFinder.callBackObject;

            if ($.isFunction(o.callbackMultipleObjects))
                window.KCFinder.callBackMultipleObjects = o.callbackMultipleObjects;
            else if (window.KCFinder && window.KCFinder.callBackMultipleObjects)
                delete window.KCFinder.callBackMultipleObjects;

        // No callbacks
        } else if (window.KCFinder)
            delete window.KCFinder;
    }

})(jQuery);
