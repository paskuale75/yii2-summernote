/*jshint esnext: true */
const summernoteS3uploader = {
    signEndpoint: '',
    bucket: '',
    folder: '',
    filenamePrefix: '',
    maxFileSize: 1024000,
    expiration: '',
    file: '',
    editor: '',

    fileSlugify: function (s) {
        var _slugify_strip_re = /[^\w\s-.]/g;
        var _slugify_hyphenate_re = /[-\s]+/g;
        s = s.replace(_slugify_strip_re, '').trim().toLowerCase();
        s = s.replace(_slugify_hyphenate_re, '-');
        return s;
    },

    getFolder: function () {
        if (typeof this.folder === 'string') {
            return this.folder;
        } else {
            return this.folder();
        }
    },

    getFilenamePrefix: function () {
        if (typeof this.filenamePrefix === 'string') {
            return this.filenamePrefix;
        } else {
            return this.filenamePrefix();
        }
    },

    sendImage: function () {
        var obj = {
            'key': this.getFolder() + this.getFilenamePrefix() + this.fileSlugify(this.file.name),
            'Content-Type': this.file.type,
            'success_action_status': '200',
            'x-amz-storage-class': 'REDUCED_REDUNDANCY',
            'acl': 'public-read',
            'bucket': this.bucket
        };

        var temp;
        var conditions = [];
        var formData = new FormData();
        for (var key in obj) {
            temp = {};
            temp[key] = obj[key];
            conditions.push(temp);
            formData.append(key, obj[key]);
        }
        conditions.push(['content-length-range', 0, this.maxFileSize]);

        var policy = {}, jsonPolicy;
        policy.expiration = this.expiration;
        policy.conditions = conditions;
        jsonPolicy = JSON.stringify(policy);

        // Sign the policy using the webserver then send the file to Amazon.
        $.ajax({
            url: this.signEndpoint,
            type: 'POST',
            data: jsonPolicy,
            dataType: 'json',
            contentType: 'application/json; charset=UTF-8',
            processData: false,
            success: function (response) {
                if (response.policy.length > 0 && response.signature.length > 0) {
                    formData.delete('bucket'); // Não enviar
                    formData.append('policy', response.policy);
                    formData.append('x-amz-signature', response.signature);
                    formData.append('x-amz-date', response.x_amz_date);
                    formData.append('x-amz-credential', response.x_amz_credential);
                    formData.append('x-amz-algorithm', response.x_amz_algorithm);
                    formData.append('file', summernoteS3uploader.file);
                    $.ajax({
                        data: formData,
                        dataType: 'xml',
                        type: 'POST',
                        cache: false,
                        contentType: false,
                        processData: false,
                        url: 'https://' + summernoteS3uploader.bucket + '.s3.amazonaws.com/',
                        success: function (data, textStatus) {
                            if (textStatus === 'success') {
                                var url = 'https://' + summernoteS3uploader.bucket + '.s3.amazonaws.com/' + summernoteS3uploader.getFolder() + summernoteS3uploader.getFilenamePrefix() + summernoteS3uploader.fileSlugify(summernoteS3uploader.file.name);
                                summernoteS3uploader.editor.summernote('insertImage', url);
                            }
                        }
                    });
                }
            }
        });
    }
};
