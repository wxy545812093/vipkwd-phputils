<!DOCTYPE html>
<html>
    <head lang="en">
        <meta charset="UTF-8">
        <title></title>
        <script src="//dl.vipkwd.com/vipkwd-cdn/js/vipkwd/vipkwd-constructor.js?S" type="text/javascript"></script>
        <script>
            console.log(utils = new VipkwdUtils());
            var types = ["video/webm", "audio/webm", "video/webm\;codecs=vp8", "video/webm\;codecs=daala", "video/webm\;codecs=h264", "audio/webm\;codecs=opus", "video/mpeg"];
            for (var i in types) {
                // console.log("Is " + types[i] + " supported? " + (MediaRecorder.isTypeSupported(types[i]) ? "Maybe!" : "Nope :("));
            }
            window.onload = function() {

                //访问用户媒体设备(摄像头和麦克风)的兼容方法
                navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;

                if (navigator.getUserMedia) {
                    //判断浏览器是否支持访问媒体设备
                    navigator.getUserMedia({
                        video: true,
                        audio: false
                    }, onSuccess, onError);
                    //打开摄像头或麦克风，并获取视频流
                } else {
                    alert("你的浏览器不支持访问用户媒体设备");
                }
                var duration_time = 15; //最大录制视频时长
                var _chunks = [];
                var oPlayer = document.getElementById("player");
                var oVideo = document.getElementById("video");
                var oStart = document.getElementById("start");
                var oStop = document.getElementById("stop");
                var oSave = document.getElementById("save");
                var blob;
                //采集的Blob格式的视频数据
                var _stream;
                //调用媒体设备成功时的回调函数
                function onSuccess(stream) {
                    oVideo.srcObject = stream;
                    //将视频流设置为video元素的源，直接播放视频
                    var mediaRecorder = new MediaRecorder(stream,{
                        //创建MediaStream 对象用来进行视频录制操作，有两个参数第一个是stream音视频流，第二个是配置参数
                        audioBitsPerSecond: 0,//128000,
                        // 音频码率
                        videoBitsPerSecond: 3500000,
                        // 视频码率
                        mimeType: 'video/webm;codecs=h264'// 编码格式
                        // mimeType : 'video/mp4;codecs="avc1.42E01E, mp4a.40.2"' // 编码格式
                    });

                    var buildBolb = ()=>{
                        const fullBlob = new Blob(_chunks,{
                            type: 'video/mp4'
                        });
                        const blobURL = window.URL.createObjectURL(fullBlob);
                        // console.log("blob is ?, size="+parseInt(fullBlob.size/1024)+"KB. ");
                        // console.log(fullBlob);
                        // console.log("blobURL =" + blobURL);
                        return blobURL;
                    }
                    ;

                    // plugin.media.addEventListener('dataavailable', e => plugin.onRecordingData(e.data));
                    // mediaRecorder.addEventListener('oStart', _ => alert(1));
                    // plugin.media.addEventListener('stop', _ => plugin.onRecordingStateChange(1));
                    // plugin.media.addEventListener('pause', _ => plugin.onRecordingStateChange(2));
                    // plugin.media.addEventListener('resume', _ => plugin.onRecordingStateChange(3));

                    oStart.onclick = function() {
                        //开始录像
                        _chunks = [];
                        mediaRecorder.start(0);
                        this.style.display = 'none';
                        oStop.style.display = 'inline-block';
                        oSave.style.display = 'none';
                        duration(true);
                    }

                    oStop.onclick = function() {
                        //停止录像
                        mediaRecorder.stop();
                        this.style.display = 'none';
                        oSave.style.display = 'inline-block';
                        oStart.style.display = 'inline-block';
                        duration(false);

                        return;
                        let source = document.createElement('video');
                        source.id = "player1";
                        source.setAttribute('src', buildBolb());
                        source.setAttribute('type', 'video/mp4');
                        oPlayer.childNodes = null
                        oPlayer.appendChild(source);
                        document.getElementById('player1').play();
                    }

                    oSave.onclick = function() {
                        //保存视频
                        blobURL = buildBolb();
                        return uploadFile();
                        return saveFile(blobURL);
                        // return uploadFile(fullBlob);
                    }

                    // 事件,开始录像时捕获数据，结束录像时将捕获的数据，传递到BLOB中，当此动作完成后，触发ondataavailable
                    mediaRecorder.ondataavailable = function(e) {
                        //   blob = new Blob([e.data], { 'type' : 'video/mp4' })
                        console.log("# 产生录制数据...");
                        // console.log(e);
                        // console.log("# ondataavailable, size = " + parseInt(e.data.size/1024) + "KB");
                        _chunks.push(e.data);
                    }

                    mediaRecorder.onstop = function(e) {
                        console.log("# 录制终止 ...");
                        buildBolb();
                    }

                }
                // 保存文件(产生下载的效果)
                let duration_timer;
                let saveFile = function(blob) {
                    const link = document.createElement('a');
                    link.style.display = 'none';
                    link.href = blob;
                    link.download = 'media_.mp4';
                    // document.body.appendChild(link);
                    link.click();
                    link.remove();
                    return;
                    // 下载视频
                    let a = document.createElement('a');
                    //创建<a>标签
                    a.href = blobURL;
                    //将视频数据的地址赋予href属性
                    a.download = `test.mp4`;
                    //将视频数据保存在当地，文件名为"test.mp4"
                    a.click();
                }
                let uploadFile = function() {
                    var blob = new Blob(_chunks,{
                        type: 'video/mp4'
                    });
                    var data = new FormData();
                    data.append('file', blob);
                    data.append('qw', 123);
                    blob = null;
                    utils.request('./video.php', data, function(res) {
                        document.querySelector("#show-info").innerHTML = utils.syntaxHighlight(res);

                        document.querySelector("#show-pre").setAttribute('style', 'width:'+ (document.querySelector("body").clientWidth - 512) + 'px')
                        
                        let span = (label, field1, field2)=>{
                            return `${label} <span style="color:${( (!field2 && field1 =='passed') || (field1 =='passed'&& field2 =='passed') ) ? 'green' : 'red'}">${field1}</span><br/>`;
                        }
                        let html = '';
                        html += span('二维评定: ', res.data.img, res.data.cv, "")
                        html += '<hr/>';
                        html += span('图像判定: ', res.data.img)
                        html += span('图像对比指数: ', res.data.debug.algorithm_distance)
                        html += span('图像对比反馈: ', res.data.img_reason)
                        html += '<hr/>';
                        html += span('视频判定: ', res.data.cv)
                        html += span('视频误差指数: ', res.data.debug.cv_out.length)
                        html += span('面部对比指数: ', res.data.debug.cv_x_distance)
                        html += span('面部对比反馈: ', res.data.cv_reason)

                        document.querySelector("#result").innerHTML = html;

                        console.log(res);
                    })
                }
                let duration = (_)=>{
                    let s = 0
                      , i = 0
                      , durationElem = document.getElementById('duration');

                    if (duration_timer)
                        clearInterval(duration_timer);
                    if (!_) {
                        const fullBlob = new Blob(_chunks);
                        // const blobURL = window.URL.createObjectURL(fullBlob);
                        durationElem.innerText += (" [ Blob size: " + parseInt(fullBlob.size / 1024) + "KB ]");
                        if(document.querySelector("#autocv").checked){
                            document.querySelector("#save").click();
                        }
                        return false;
                    }

                    duration_timer = setInterval(()=>{
                        i+=10;
                        if (i >= 1000) {
                            s += 1;
                            i = 0;
                        }
                        let sx = `${s}.` + (`000${i}`.slice(-3));
                        durationElem.innerText = `[ Duration: ${sx}]`
                        if(parseInt(sx) >= duration_time){
                            clearInterval(duration_timer);
                            document.querySelector("#stop").click();
                        }
                    }
                    , 10);

                }
                //调用媒体设备异常时的回调函数
                function onError(error) {
                    console.log("访问用户媒体设备失败：", error.name, error.message);
                }
            }
        </script>
        <style>
        html,body,* {
		    padding: 0;
		    margin: 0
		}
        body {
			display: flex;
			justify-content: flex-start;
			align-items: flex-start;
			flex-wrap: wrap;
		}
		.nav{width: 100%; height: 40px; line-height: 40px; display: flex; justify-content: center; background: #70bce0; color: #fff; font-size: 1.3rem;}
		.video-container{width: 100%;}
        </style>
    </head>
    <body>
        <div class="nav"> <a href="./image_view.php" >图像识别</a></div>
        <div class="video-container">
            <div style=" width: 500px; float: left; ">
                <!--video用于显示媒体设备的视频流，自动播放-->
                <video id="video" autoplay style="width: 500px;height: 350px"></video>
                <br>
                <div id="duration"></div>
                <input type="button" value="开始录像" id="start"/>
                <input type="button" value="停止录像" id="stop" style="display:none"/>
                <input type="button" value="面部分析" id="save" style="display:none"/>
                <label><input type="checkbox" checked id="autocv"/>自动分析</label>
            </div>
            <div>
                <div id="result"></div>
                <div id="show-info"></div>
            </div>
        </div>
        <div id="player" style="width: 500px;height: 350px" class="hide"></div>
    </body>
</html>