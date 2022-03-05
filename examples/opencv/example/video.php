<?php
require "../../autoload.php";
use Vipkwd\Utils\{Dev, Http, File, Opencv, FFmpeg, Tools, ImageHash};

$debug = 0;
$upload = 1;

$info['data']['path'] = "/Applications/MxSrvs/www/vipkwd-utils/examples/opencv/example/upload/v3DUJX8IMqIXiRBO.mp4";

$xml_dir = __DIR__.'/../opencv-3.4.3/data/haarcascades_cuda/haarcascade_frontalface_alt2.xml';

if(isset($_GET['viewPic'])){
    $pic = base64_decode(trim($_GET['viewPic']));
    Opencv::instance()
        ->faceDetect($pic, $xml_dir)
        ->drawBorder()
        ->sendPNG();
}

if(!empty(Http::request()->getFiles())){
    $info = File::upload([
        "upload_dir" => __DIR__.'/upload/',
        "type" => 'mp4,webm'
    ]);
}

// Dev::dump([$info, Http::request()->getFiles()],1);


if( !isset($info['data']['path']) || !File::exists($info['data']['path'])){
    Http::response()->send('测试文件不存在');
}


$list = (new FFmpeg)->exportImages($info['data']['path'], 1, '', '0', 15);


$algorithm_opencv = [];
$cv_distance = ['w'=>[],'h'=>[]];

$algorithm_data = [];
$algorithm_steps = [3,5,7,9,11,13,17, 19,23];
$algorithm_lens = count($algorithm_steps);
foreach($list['images'] as $k => $filename){

    $cv = Opencv::instance()->faceDetect($list['output'].'/'.$filename, $xml_dir)->data();
    $debug && $cv["pa"] = '<img src="./video.php?viewPic='.base64_encode($list['output'].'/'.$filename).'" width="200"/>';

    $cv_distance['w'][] = $cv[0]['w'] ?? 0;

    $algorithm_opencv[$filename] = $cv;
    if($k == 0) continue;

    $kk = $list['images'][$k-1].'-'.$filename;
    $algorithm_data[$kk] = ImageHash::instance()->distance(
        $list['output'].'/'.$list['images'][$k-1],
        $list['output'].'/'.$filename
    );

    foreach($algorithm_steps as $step){
        if( $k % ( $step-1) === 0 && $k != $step){
        // if( $k === ( $step-1) ){
            $pos = max(0,$step - $algorithm_lens);
            $kk = $list['images'][$pos].'-'.$filename .'#####';
            $algorithm_data[$kk] = ImageHash::instance()->distance(
                $list['output'].'/'.$list['images'][$pos],
                $list['output'].'/'.$filename
            );
        }
    }
}


$cv_distance = ['w'=>[],'h'=>[]];
foreach($algorithm_opencv as $k => $item){
    $cv_distance['w'][] = $item[0]['w'] ?? 0;
    $cv_distance['h'][] = $item[0]['h'] ?? 0;
}

//图片相似度 AVG
$algorithm_distance = floatval(bcdiv(array_sum($algorithm_data), count($algorithm_data), 4));

$result = [
    "img_pass" => ($algorithm_distance < 80 || $algorithm_distance > 90 ) ? "fail" : "passed",
    "img_num" => $algorithm_distance,
    // "cv"  => $cv_distance,
    "cv_size_avg" => [
        'w' => bcdiv(array_sum($cv_distance['w']) / count($cv_distance['w']) , 1),
        'h' => bcdiv(array_sum($cv_distance['h']) / count($cv_distance['h']) , 1),
    ],
];

$mins = array_filter($cv_distance['w'],function($w) use($result){
    //检测宽度 小于  所有图片宽度平均值的 有几张
    return ($w < $result['cv_size_avg']['w']);
});
$result['cv_pass'] =  (count($mins) >= 3) ? "fail" : "passed";

//清理资源
File::delete($list['source']);
foreach($list['images'] as $filename){
    File::delete($list['output'].'/'.$filename);
}

$debug && Dev::dumper([$mins, $result]);

$debug && Dev::dumper([$algorithm_opencv, $algorithm_data, $list, $info, Http::request()->files]);


if(!$debug){
    $response = [
        "img" => $result['img_pass'],
        "cv" => $result['cv_pass'],

        "debug" => [
            "info" => $info,
            "list" => $list,
            "xml_dir" => $xml_dir,
            "algorithm_opencv" => $algorithm_opencv,
            "cv_distance" => $cv_distance,
            "algorithm_data" => $algorithm_data,
            "algorithm_steps" => $algorithm_steps,
            "algorithm_lens" => $algorithm_lens,
            "algorithm_distance" => $algorithm_distance,
            "mins" => $mins,
        ]
    ];
    unset(
        $info, $xml_dir, $list,
        $algorithm_opencv, $cv_distance,
        $algorithm_data, $algorithm_steps, $algorithm_lens, $algorithm_distance,
        $result, $mins
    );

    // Dev::dump($response,1);
    Http::response()->send($response);
}

