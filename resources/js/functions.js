/* ajax call template */
function ajaxCall(url, method, data, contentType, processData, successCallback, validationErrorCallback) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method,
            data,
            contentType,
            processData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            statusCode: {
                200: resolve,
                201: resolve,
                401: () => reject('Unauthorized. Please refresh the page or login again.'),
                403: () => reject('Access forbidden. You don\'t have enough permission.'),
                413: () => reject('File is too large. Make sure your file is less than 5 MB.'),
                419: () => reject('CSRF token mismatch. Please refresh the page and try again.'),
                422: (data) => {
                    validationErrorCallback(data);
                    reject('Validation errors found.');
                },
                500: (data) => {
                    let msg = 'Internal server error. Please try again later.';
                    if (data?.responseJSON?.error) {
                        msg = data.responseJSON.error;
                    }
                    reject(msg);
                },
                502: () => reject('Bad gateway error. Please try again later.'),
                504: () => reject('Server timeout. Please try again later.')
            },
            success: successCallback,
            error: (xhr, status, error) => {
                if (![200, 201, 401, 403, 413, 419, 422, 500, 502, 504].includes(xhr.status)) {
                    reject(`${error} (${xhr.status})`);
                }
            }
        });
    });
}

function thousandSeparator(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
}
