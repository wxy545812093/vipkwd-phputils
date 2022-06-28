<?php
	// include('../../autoload.php');
	// use Vipkwd\Utils\System\File;
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
	<meta charset="utf-8">
	<title>HTML5大文件分片上传示例</title>
	<script src="/vipkwd-utils/examples/hashFileUpload/js/spark-md5.js"></script>
	<script src="/vipkwd-utils/examples/hashFileUpload/js/sha1.js"></script>
	<script src="/vipkwd-utils/examples/hashFileUpload/js/common.js"></script>
	<script type="text/javascript">
		let url, haarcascades, setImage = (xml = '') => {
			if (!url) { return false; }
			let args = [
				url,
				`color=${opencv_color.value.slice(1)}`,
				`border=${opencv_border.value}`,
				`deg=${opencv_deg.value}`,
				`xml=${xml}`,
			].join("&");

			document.getElementById('vipkwd-opencv-img').innerHTML = `<img style="width:500px" src="${args}" />`;
		}, init = () => {
			//使用默认上传视图
			(new fileUploadPage).html({
				containerId: 'container',
				autoUpload: 1,
				api: {
					upload: "./upload.php",
					status: "./status.php",
					download: "./download.php?"
				},
				complete: (obj) => {
					url = `./opencv_process.php?type=${obj.type}&name=${obj.name}&hash=${obj.md5Hash}_${obj.sha1Hash}`;
					setImage((url.indexOf('type=image/') > 0) ? haarcascades : '');
					document.getElementById('haarcascades-selector').style.display = (url.indexOf('type=image/') > 0) ? "block" : "none";
					// console.log(obj)
				}
			});
			/*
			//自定义视图，页面需要自己写
			(new fileUploadPage).init({
				container: 'container',
				autoUpload: false,
				elem:{
					output: "#output",
					uploadBtn: "#upload",
					fileInput: "#file",
					processBar: "#vipkwd-upload-bar"
				},
				api:{
					upload: "./upload.php",
					status: "./status.php",
					download: "./download.php?"
				}
			});*/
		};
		function change(self) {
			haarcascades = self.value;
			setImage(self.value);
		}
	</script>
	<style>
		html,
		body,
		* {
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
		
		#container {
		    display: flex;
		    flex-direction: column-reverse;
		    width: 500px;
		    justify-content: flex-end;
		    height: 99%;
		    border: 1px dashed #008000;
		}
		/* #vipkwd-opencv-img{margin-top:12px} */
		
		.haarcascades-selector {
		    margin: 0 2rem;
		    display: block
		}
	</style>
</head>

<body onload="init();">
	<div class="nav"> <a href="./video_view.php" >H5视频识别</a></div>
	<div style=" display: flex;">
		<div id="container">
			<div id="vipkwd-opencv-img">
				<img style="width:500px" src="./opencv_process.php">
			</div>
		</div>
		<div class="haarcascades-selector" id="haarcascades-selector">
			<div>
				扭曲角度：<input type="number" id="opencv_deg" min="0" name="opencv_deg" value="0" /><BR/>
				线框宽度：<input type="number" id="opencv_border" min="2" name="opencv_border" value="4" /><BR/>
				线框颜色：<input type="color" id="opencv_color" name="opencv_color" value="#fc02fc" /><BR/>
				识别模型：<ul>
				<?php
				foreach(glob(__DIR__.'/../opencv-3.4.3/data/haarcascades_cuda/*.xml') as $file){

					echo '<li><label for="'.basename($file).'"><input type="radio" onclick="return change(this)" id="'.basename($file).'" name="haarcascades" value="'.basename($file).'" /> '.basename($file).'</label></li>';
					// echo '<option value="'.basename($file).'">'.basename($file).'</option>';
				};
				?> 
				</ul> 
			</div>
		</div>
    </div>
</body>
</html>