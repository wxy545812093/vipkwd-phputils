//实现document.ready(fn);
(function () {
    var ie =!!(window.attachEvent&&!window.opera),wk=/webkit\/(\d+)/i.test(navigator.userAgent)&&(RegExp.$1<525);
    var fn =[],run=function(){for(var i=0;i<fn.length;i++)fn[i]();},d=document;d.ready=function(f){
    if(!ie&&!wk&&d.addEventListener){return d.addEventListener('DOMContentLoaded',f,false);}if(fn.push(f)>1)return;
    if(ie)(function(){try{d.documentElement.doScroll('left');run();}catch(err){setTimeout(arguments.callee,0);}})();
    else if(wk)var t=setInterval(function(){if(/^(loaded|complete)$/.test(d.readyState))clearInterval(t),run();},0);};
})();

var fileUploadPage = function(){

    function md5File(file, callback) {
        var fileReader = new FileReader(),
            // box=document.getElementById('box');
            blobSlice = File.prototype.mozSlice || File.prototype.webkitSlice || File.prototype.slice,
            // file = document.getElementById("file").files[0],
            chunkSize = 2097152,
            // read in chunks of 2MB
            chunks = Math.ceil(file.size / chunkSize),
            currentChunk = 0,
            spark = new SparkMD5();

        fileReader.onload = function (e) {
            console.log("read chunk md5 ", currentChunk + 1, "of", chunks);
            spark.appendBinary(e.target.result); // append binary string
            currentChunk++;

            if (currentChunk < chunks) {
                loadNext();
            }
            else {
                console.log("finished loading");
                // box.innerText='MD5 hash:'+spark.end();
                var MD5Hash = spark.end();
                console.info("computed hash", MD5Hash); // compute hash
                callback(MD5Hash)
            }
        };

        function loadNext() {
            var start = currentChunk * chunkSize,
                end = start + chunkSize >= file.size ? file.size : start + chunkSize;

            fileReader.readAsBinaryString(blobSlice.call(file, start, end));
        };

        loadNext();
    }

    /*
    * sha1File v1.0.1
    * https://github.com/dwsVad/sha1File
    * (c) 2014 by Protsenko Vadim. All rights reserved.
    * https://github.com/dwsVad/sha1File/blob/master/LICENSE
    */
    function sha1File(settings, callback) {
        var hash = [1732584193, -271733879, -1732584194, 271733878, -1009589776];
        var buffer = 1024 * 16 * 64;
        var currentChunk = 0;
        // read in chunks of 2MB
        var chunks = Math.ceil(settings.size / buffer);
        var sha1 = function (block, hash) {
            var words = [];
            var count_parts = 16;
            var h0 = hash[0],
                h1 = hash[1],
                h2 = hash[2],
                h3 = hash[3],
                h4 = hash[4];
            for (var i = 0; i < block.length; i += count_parts) {
                var th0 = h0,
                    th1 = h1,
                    th2 = h2,
                    th3 = h3,
                    th4 = h4;
                for (var j = 0; j < 80; j++) {
                    if (j < count_parts)
                        words[j] = block[i + j] | 0;
                    else {
                        var n = words[j - 3] ^ words[j - 8] ^ words[j - 14] ^ words[j - count_parts];
                        words[j] = (n << 1) | (n >>> 31);
                    }
                    var f, k;
                    if (j < 20) {
                        f = (h1 & h2 | ~h1 & h3);
                        k = 1518500249;
                    }
                    else if (j < 40) {
                        f = (h1 ^ h2 ^ h3);
                        k = 1859775393;
                    }
                    else if (j < 60) {
                        f = (h1 & h2 | h1 & h3 | h2 & h3);
                        k = -1894007588;
                    }
                    else {
                        f = (h1 ^ h2 ^ h3);
                        k = -899497514;
                    }

                    var t = ((h0 << 5) | (h0 >>> 27)) + h4 + (words[j] >>> 0) + f + k;
                    h4 = h3;
                    h3 = h2;
                    h2 = (h1 << 30) | (h1 >>> 2);
                    h1 = h0;
                    h0 = t;
                }
                h0 = (h0 + th0) | 0;
                h1 = (h1 + th1) | 0;
                h2 = (h2 + th2) | 0;
                h3 = (h3 + th3) | 0;
                h4 = (h4 + th4) | 0;
            }
            return [h0, h1, h2, h3, h4];
        }

        var run = function (file, inStart, inEnd) {
            var end = Math.min(inEnd, file.size);
            var start = inStart;
            var reader = new FileReader();

            reader.onload = function () {
                console.log("read chunk sha1 ", currentChunk + 1, "of", chunks);
                file.sha1_progress = (end * 100 / file.size);
                var event = event || window.event;
                var result = event.result || event.target.result
                var block = Crypto.util.bytesToWords(new Uint8Array(result));

                if (end === file.size) {
                    var bTotal, bLeft, bTotalH, bTotalL;
                    bTotal = file.size * 8;
                    bLeft = (end - start) * 8;

                    bTotalH = Math.floor(bTotal / 0x100000000);
                    bTotalL = bTotal & 0xFFFFFFFF;

                    // Padding
                    block[bLeft >>> 5] |= 0x80 << (24 - bLeft % 32);
                    block[((bLeft + 64 >>> 9) << 4) + 14] = bTotalH;
                    block[((bLeft + 64 >>> 9) << 4) + 15] = bTotalL;

                    hash = sha1(block, hash);
                    file.sha1_hash = Crypto.util.bytesToHex(Crypto.util.wordsToBytes(hash));
                    console.log(file.sha1_hash)
                    currentChunk++;
                    callback(file.sha1_hash)
                }
                else {
                    hash = sha1(block, hash);
                    start += buffer;
                    end += buffer;
                    currentChunk++;
                    run(file, start, end);
                }
            }
            var blob = file.slice(start, end);
            reader.readAsArrayBuffer(blob);
        }

        var checkApi = function () {
            if ((typeof File == 'undefined'))
                return false;

            if (!File.prototype.slice) {
                if (File.prototype.webkitSlice)
                    File.prototype.slice = File.prototype.webkitSlice;
                else if (File.prototype.mozSlice)
                    File.prototype.slice = File.prototype.mozSlice;
            }

            if (!window.File || !window.FileReader || !window.FileList || !window.Blob || !File.prototype.slice)
                return false;

            return true;
        }

        if (checkApi()) {
            run(settings, 0, buffer);
        }
        else
            return false;
    }

    /**
     * 上传文件类
     * @constructor
     */
    function fileUploadClass(options) {
        this.shardSize = 2 * 1024 * 1024;  //分块大小size
        this.name = '';  //文件名称
        this.size = 0;  //总大小
        this.md5Hash = '';  //文件md5Hash
        this.sha1Hash = '';  //文件sha1Hash
        this.file = null;  //文件
        this.shardCount = 0;  //总片数
        this.succeed = 0;  //上传个数
        this.uploadSuccess = false;
        this.shardList = []; //已上传分块列表
        this.uploadIndex = 0;
        this.debug = false;
        this.jq = typeof jQuery != "undefined" ? jQuery : null;
        this.options = options;
    }

    fileUploadClass.prototype = {
        constructor: fileUploadClass,
        options:{},
        shardInit: function (file) {
            this.file = file;   //文件名
            this.name = file.name;   //文件名
            this.size = file.size;       //总大小
            this.shardCount = Math.ceil(this.size / this.shardSize);  //总片数
        },
        uploadTo: function (file, md5Hash, sha1Hash) {
            this.setProgress(0);
            this.upload(file, md5Hash, sha1Hash, 10);
        },
        setProgress:function(block_index){
            if(this.options.elem.processBar){
                let processElemObj = document.querySelector(this.options.elem.processBar),
                process = Math.min(100,  Math.ceil( (block_index / this.shardCount) *100) );
                processElemObj.style.width = process +"%"; 
                processElemObj.innerHTML ="<span>"+process+"%</span>";
            }
            return this;
        },
        outputText:function(text){
            document.querySelector(this.options.elem.output).innerText = text;
        },
        upload: function (file, md5Hash, sha1Hash, batchUploadCount) {
            var bitchCountRecord = batchUploadCount;
            var shardSize = this.shardSize;   //以2MB为一个分片
            var name = this.name;   //文件名
            var size = this.size;       //总大小
            var shardCount = this.shardCount;  //总片数
            for (var i = this.uploadIndex; i < this.uploadIndex + batchUploadCount; ++i) {
                if (i >= this.shardCount) {
                    this.debug && console.log('遍历完全部的块了');
                    return;
                }
                if (this.uploadSuccess) {
                    this.debug && console.log('上传成功');
                    return;
                }
                if (this.shardList.length > 0 && this.shardList.indexOf(i + 1) > 0) {
                    this.succeed++;
                    this.outputText(this.succeed + " / " + shardCount);
                    batchUploadCount--;
                    if (batchUploadCount <= 0) {
                        this.uploadIndex += bitchCountRecord;
                        this.upload(file, md5Hash, sha1Hash, bitchCountRecord);
                    }
                    continue;
                }
                //计算每一片的起始与结束位置
                var start = i * shardSize,
                    end = Math.min(size, start + shardSize);
                //构造一个表单，FormData是HTML5新增的
                var form = new FormData();
                form.append("data", file.slice(start, end));  //slice方法用于切出文件的一部分
                form.append("name", name);
                form.append("total", shardCount);  //总片数
                form.append("md5Hash", md5Hash);  //md5Hash
                form.append("sha1Hash", sha1Hash);  //sha1Hash
                form.append('size', this.size);
                form.append('shardSize', this.shardSize);
                form.append("index", i + 1);        //当前是第几片
                var fileUploadObj = this;
                fileUploadObj.ajax({
                    url: this.options.api.upload,
                    type: "POST",
                    data: form,
                    async: true,        //异步
                    processData: false,  //很重要，告诉jquery不要对form进行处理
                    contentType: false,  //很重要，指定为false才能形成正确的Content-Type
                    success: function (data) {
                        batchUploadCount--;
                        if (parseInt(data.status) === 0) {
                            fileUploadObj.debug && console.log('该分片上传失败' + (i + 1));
                            return;
                        }
                        if(parseInt(data.status) === -1){
                            fileUploadObj.outputText("上传失败，系统磁盘空间不足");
                            return;
                        }
                        fileUploadObj.succeed++;
                        fileUploadObj.outputText(fileUploadObj.succeed + " / " + shardCount);
                        fileUploadObj.setProgress(fileUploadObj.succeed);
                        if (batchUploadCount <= 0) {
                            fileUploadObj.uploadIndex += bitchCountRecord;
                            fileUploadObj.upload(file, md5Hash, sha1Hash, bitchCountRecord);
                        }
                    },
                    error: function (data) {
                        fileUploadObj.debug && console.log(data);
                        fileUploadObj.debug && console.log('该分片上传失败' + (i + 1));
                        batchUploadCount--;
                        if (batchUploadCount <= 0) {
                            fileUploadObj.uploadIndex += bitchCountRecord;
                            fileUploadObj.upload(file, md5Hash, sha1Hash, bitchCountRecord);
                        }
                    }
                });
            }
        },
        monitor: function (callback) {
            var form = new FormData();
            form.append('md5Hash', this.md5Hash);
            form.append('sha1Hash', this.sha1Hash);
            form.append('total', this.shardCount);
            form.append('size', this.size);
            form.append('shardSize', this.shardSize);
            var fileUploadObj = this;
            fileUploadObj.ajax({
                url: this.options.api.status,
                type: "POST",
                data: form,
                async: true,        //异步
                processData: false,  //很重要，告诉jquery不要对form进行处理
                contentType: false,  //很重要，指定为false才能形成正确的Content-Type
                success: function (data) {
                    fileUploadObj.debug && console.log(data);
                    fileUploadObj.shardList = data.data.list;
                    if (parseInt(data.status) === 1) {  //上传成功
                        fileUploadObj.uploadSuccess = true;
                        fileUploadObj.setProgress(fileUploadObj.shardCount);

                        downUrl = fileUploadObj.options.api.download.replace(/(.*?)(\?+)$/, "$1") + '?md5Hash=' + fileUploadObj.md5Hash;
                        downUrl+= '&sha1Hash=' + fileUploadObj.sha1Hash;
                        downUrl+= '&name=' + encodeURIComponent(fileUploadObj.name);

                        document.querySelector(fileUploadObj.options.elem.output).innerHTML = (fileUploadObj.shardCount + " / " + fileUploadObj.shardCount + '（上传成功）<a href="' + downUrl + '" target="_blank">下载</a>');
                        fileUploadObj.debug && console.log('上传成功monitor');
                        document.querySelector(fileUploadObj.options.elem.uploadBtn).removeAttribute("disabled");
                        return;
                    }
                    if (callback !== undefined) {
                        callback();
                    }
                    window.setTimeout(function () {
                        fileUploadObj.monitor();
                    }, 1000);
                },
                error: function (data) {
                    fileUploadObj.debug && console.log(data);
                    window.setTimeout(function () {
                        fileUploadObj.monitor();
                    }, 1000);
                }
            });
        },
        isFunc:(obj)=>{
            return Object.prototype.toString.call(obj) === "[object Function]";
        },
        ajax:function(options){
            var fileUploadObj = this;
            if(fileUploadObj.jq !== null){
                fileUploadObj.jq.ajax(options);
            }else{
                const xhr = new XMLHttpRequest();
                xhr.responseType = "json";
                xhr.onerror = function(e){
                    fileUploadObj.isFunc(options.error) && options.error(e);
                };
                xhr.onreadystatechange = function(){
                    if (xhr.readyState === xhr.DONE && xhr.status === 200) {
                        fileUploadObj.isFunc(options.success) && options.success(xhr.response);
                    }
                };
                xhr.open(options.type, options.url, options.async);
                xhr.send(options.data);
            }
        }
    };

    var page = {
        init: function (options) {
            var that = this;
            document.querySelector(options.elem.uploadBtn).addEventListener("click", function(){
                that.upload.call(that, options);
            });
        },
        upload: function (options) {
            document.querySelector(options.elem.uploadBtn).setAttribute("disabled", "disabled");
            var fileUploadObj = new fileUploadClass(options);
            var file = document.querySelector(options.elem.fileInput).files[0]; //文件对象
            fileUploadObj.file = file;
            fileUploadObj.shardInit(file);
            document.querySelector(options.elem.output).innerText = '文件识别中...';
            md5File(file, function (md5Hash) {
                fileUploadObj.md5Hash = md5Hash;
                sha1File(file, function (sha1Hash) {
                    fileUploadObj.sha1Hash = sha1Hash;
                    fileUploadObj.monitor(function () {
                        fileUploadObj.uploadTo(file, md5Hash, sha1Hash);
                    });
                });
            });
        }
    };
    //page.init(options);
    return {
        html: (options)=>{
            if(!options.elem || !options.elem.uploadBtn || !options.elem.output || !options.elem.fileInput 
                || document.querySelector(options.elem.uploadBtn) === null 
                || document.querySelector(options.elem.output) === null 
                || document.querySelector(options.elem.fileInput) === null 
            ){
                let div, hax = Math.floor(Math.random() * 1e6);
                options['elem'] = {
                    output: "#output"+hax,
                    uploadBtn: "#upload"+hax,
                    fileInput: "#file"+hax,
                    processBar: "#vipkwd-upload-bar"+hax
                };
                div = document.createElement('div');
                div.setAttribute('id', "upload-container"+hax);
                div.innerHTML = `<style> #vipkwd-upload-progress${hax}{ width:500px; height:30px; line-height:30px; border:1px solid green; position: relative; } #vipkwd-upload-bar${hax}{ width:0%; height:10px; background-color: green; } #vipkwd-upload-bar${hax} >span{ position: absolute; right: 6px; font-size: 10px; line-height: 10px; } </style>
                    <div id="vipkwd-upload-progress${hax}">
                        <input type="file" id="file${hax}"/>
                        <button id="upload${hax}">上传</button>
                        <span id="output${hax}" style="font-size:12px">等待</span>
                        <div id="vipkwd-upload-bar${hax}"><span></span></div>
                    </div>`
                document.body.appendChild(div);
            }
            page.init(options);
        },
        init: (options)=>{
            page.init(options)
        }
    };
}