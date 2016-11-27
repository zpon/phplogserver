/*global define, console*/
define(['jquery'], function (jq) {
    'use strict';
    var logServer = "http://localhost:1234/php/logger.php";

    return {
        log: function (object) {
            jq.ajax({
                url: logServer,
                method: 'POST',
                data: {
                    "data": JSON.stringify(object),
                    "page": window.location.href,
                    "stack": (new Error()).stack
                },
                success: function (result) {
                    // console.log("success: " + result);
                },
                error: function (jqXHR) {
                    console.warn("Unable to log error. Status: " + jqXHR.status + ". Data: " + (jqXHR.responseText || "No data"));
                }
            });
        },
        logAjax(jqXHR, textStatus, errorThrown, jqResult) {
            this.log({
                "url": jqResult.url,
                "type": jqResult.type,
                "textStatus": textStatus,
                "errorThrown": errorThrown,
                "jqXHR.status": jqXHR.status,
                "jqXHR.responseText": (jqXHR.responseText || "- undefined -")
            });
        }
    }
});