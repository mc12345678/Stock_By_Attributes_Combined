// $Id: products_with_attributes_stock_ajax.js 389 2008-11-14 16:02:14Z hugo13 $
//Stock by Attributes 1.5.4 (supports jquery-1.10.2.min.js)

//stockAttributesCellQuantity
function addEvent() {
    "use strict";

    $(".stockAttributesCellQuantity").each(function (iter) {
        var curcelltitle = this;
        if (!curcelltitle.haseventhandler) {
            $(curcelltitle).click(function (event) {
            /* Our Eventhanderl */
                var tgt = event.target;
                var id = tgt.id;
                var inner = tgt.innerHTML;
                if (!$(tgt).is("input")) {
                    var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"8\"/>";
                    tgt.innerHTML = newLi;
                    //this.unbind("click");
                }
            });
            curcelltitle.haseventhandler = true;
        }
    });

//stockAttributesCellSort
    $(".stockAttributesCellSort").each(function (iter) {
        var curcelltitle = this;
        if (!curcelltitle.haseventhandler) {
            $(curcelltitle).click(function (event) {
            /* Our Eventhanderl */
                var tgt = event.target;
                var id = tgt.id;
                var inner = tgt.innerHTML;
                if (!$(tgt).is("input")) {
                    var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"8\"/>";
                    tgt.innerHTML = newLi;
                    //this.unbind("click");
                }
            });
            curcelltitle.haseventhandler = true;
        }
    });

//stockAttributesCellCustomid
    $(".stockAttributesCellCustomid").each(function (iter) {
        var curcelltitle = this;
        if (!curcelltitle.haseventhandler) {
            $(curcelltitle).click(function (event) {
                /* Our Eventhanderl */
                var tgt = event.target;
                var id = tgt.id;
                var inner = tgt.innerHTML;
                if (!$(tgt).is("input")) {
                    var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"8\"/>";
                    tgt.innerHTML = newLi;
                    //this.unbind("click");
                }
            });
            curcelltitle.haseventhandler = true;
        }
    });
//}

//stockAttributesCellTitle
//function addEvent() {
    $(".stockAttributesCellTitle").each(function (iter) {
        var curcelltitle = this;
        if (!curcelltitle.haseventhandler) {
            $(curcelltitle).click(function (event) {
                /* Our Eventhanderl */
                var tgt = event.target;
                var id = tgt.id;
                var inner = tgt.innerHTML;
                if (!$(tgt).is("input")) {
                    var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"25\"/>";
                    tgt.innerHTML = newLi;
                    //this.unbind("click");
                }
            });
            curcelltitle.haseventhandler = true;
        }
    });
}

function saved(responseText, statusText) {
    "use strict";
    alert("status: " + statusText + "\n\nresponseText: \n" + responseText);
}

$(document).ready(function () {
    "use strict";
    // bind form using ajaxForm
    $("#pwas-search").ajaxForm({
        // target identifies the element(s) to update with the server response
        target: "#pwa-table",
        url: "products_with_attributes_stock_ajax.php",
        success: addEvent

        // success identifies the function to invoke when the server response
        // has been received; here we apply a fade-in effect to the new content
    });
});

$(document).ready(function () {
    "use strict";
    // bind form using ajaxForm
    $("#store").ajaxForm({
        // target identifies the element(s) to update with the server response
        target: "#hugo1",
        success: saved

        // success identifies the function to invoke when the server response
        // has been received; here we apply a fade-in effect to the new content
    });
});


$(document).ready(function () {
    "use strict";
//TODO: Look at the URL: should this be a random generator entry
//    $("#btnrandom").click(function (e) {
//        e.preventDefault(); // Normales Submit unterdrücken

//        $.ajax({ // AJAX Request auslösen
//            type: "POST",
//            url: "/data/random/5a0bc3836e07a7be06a2fc3109b9d9daaffeafda/1",
//            dataType: "json",
//            global: "false",
//            success: processJason
//        });
//    });

    $("#loading").hide();    // Das Loding Element verstecken

    //class identified item to be edited as an input text string
    $(".editthis").click(function (event) {
        var tgt = event.target;
        var id = tgt.id;
        var inner = tgt.innerHTML;
        if (!$(tgt).is("input")) {
            if (!tgt.haseventhandler) {
                var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"8\"/>";
                tgt.innerHTML = newLi;
                tgt.haseventhandler = true;
            }
        }
        $("#" + tgt.id + " input").focus();
    });

    //stockAttributesCellTitle
    $(".stockAttributesCellTitle").click(function (event) {
        var tgt = event.target;
        var id = tgt.id;
        var inner = tgt.innerHTML;
        if (!$(tgt).is("input")) {
            if (!tgt.haseventhandler) {
                var newLi = "<input type=\"text\" name=\"" + id + "\" id=\"" + id + "\" value=\"" + inner + "\" size=\"25\"/>";
                tgt.innerHTML = newLi;
                tgt.haseventhandler = true;
            }
        }
        $("#" + tgt.id + " input").focus();
    });

    $("#pwas-filter").focus();
});


