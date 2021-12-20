function getRemainingRequest (apiName) {
    return new Promise((res,rej) => {
        jQuery.ajax({
            url: "ws.php?format=json&method=pwg.tagRecognition.getRemainingRequest",
            type: "POST",
            data: {
            api : apiName,
            pwg_token : pwg_token,
            },
            success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat == 'ok')
                res(data.result);
            else
                rej(data);
            },
            error: function(message) {
            rej(message);
            }
        });
    });
}

function generateTagsFromAPI(apiName, language, imageId, limit) {
    return new Promise((res, rej) => {
        jQuery.ajax({
            url: "ws.php?format=json&method=pwg.tagRecognition.getTags",
            type: "POST",
            data: {
            api : apiName,
            language : language,
            imageId : imageId,
            limit : limit,
            pwg_token : pwg_token,
            },
            success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat == 'ok')
                res(data.result);
            else
                rej(data);
            },
            error: function(message) {
            rej(message);
            }
        });
    })
}

function createAndAssignTags(tags, imageId) {
    return new Promise((res, rej) => {
        jQuery.ajax({
            url: "ws.php?format=json&method=pwg.tagRecognition.createAndAssignTags",
            type: "POST",
            data: {
            tags : tags,
            imageId : imageId,
            pwg_token : pwg_token,
            },
            success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat == 'ok')
                res(data.result);
            else
                rej(data);
            },
            error: function(message) {
            rej(message);
            }
        });
    })
}