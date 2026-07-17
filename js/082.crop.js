/** 
 *   @desc Image Crop
 *   @package kcfinder-Resurrected
 *   @version 4.0
 *   @license http://opensource.org/licenses/GPL-3.0 GPLv3
 *   @license http://opensource.org/licenses/LGPL-3.0 LGPLv3
 */
var size;

function normalizeCropDimensions(selection) {
    var image = document.getElementById('RecortarImagen'),
        rawX = Number(selection && selection.x),
        rawY = Number(selection && selection.y),
        rawWidth = Number(selection && selection.w),
        rawHeight = Number(selection && selection.h),
        naturalWidth,
        naturalHeight,
        x,
        y,
        right,
        bottom;

    if (!image ||
        !isFinite(rawX) || !isFinite(rawY) ||
        !isFinite(rawWidth) || !isFinite(rawHeight) ||
        rawWidth <= 0 || rawHeight <= 0
    )
        return false;

    naturalWidth = image.naturalWidth || Math.ceil(rawX + rawWidth);
    naturalHeight = image.naturalHeight || Math.ceil(rawY + rawHeight);
    x = Math.max(0, Math.floor(rawX));
    y = Math.max(0, Math.floor(rawY));
    right = Math.min(naturalWidth, Math.ceil(rawX + rawWidth));
    bottom = Math.min(naturalHeight, Math.ceil(rawY + rawHeight));

    if (right <= x || bottom <= y)
        return false;

    return {
        x: x,
        y: y,
        w: right - x,
        h: bottom - y
    };
}

_.cropImage = function (file) {
    var url, data = new FormData();
    _.imageCropDialog({
            upload: _.uploadURL,
            dir: _.dir,
            file: encodeURIComponent(file.name)
        }, {
            title: encodeURIComponent(file.name)
        },
        function () {
            var dimensions = normalizeCropDimensions(size);

            if (!dimensions) {
                _.alert(_.label("Invalid crop dimensions."));
                return false;
            }

            url = _.getURL('crop');
            data.append('file', file.name);
            data.append('dir', _.dir);
            data.append('x', dimensions.x);
            data.append('y', dimensions.y);
            data.append('w', dimensions.w);
            data.append('h', dimensions.h);
            // Token csrf
            data.append('csrf_token', csrfToken);
            $.ajax({
                type: "post",
                url: url,
                dataType: 'json',
                contentType: false,
                processData: false,
                cache: false,
                data: data,
                beforeSend: function () {
                    $('#loading').html(_.label("Croping file...")).show();
                },
                success: function (resp) {
                    if (_.check4errors(resp))
                        return;
                    _.refresh();
                },
                error: function (xhr) {
                    console.log(xhr.responseText);
                    _.alert(_.label(xhr.responseText));
                },
                complete: function () {
                    $('#loading').hide();
                }
            });
            return true;
        }
    );
    return false;
};

function loadCrop() {
    $('#RecortarImagen').Jcrop({
        setSelect: [0, 0, 150, 180],
        boxWidth: 500,
        boxHeight: 380,
        //aspectRatio: 1,
        onSelect: function (c) {
            size = {
                x: c.x,
                y: c.y,
                w: c.w,
                h: c.h
            };
        }
    });
}
