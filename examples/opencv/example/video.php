<?php
require "../../autoload.php";

use Vipkwd\Utils\Dev;
use Vipkwd\Utils\Http;
use Vipkwd\Utils\MediumAI\Opencv;
use Vipkwd\Utils\MediumAI\FFmpeg;
use Vipkwd\Utils\Image\ImageHash;
use Vipkwd\Utils\System\File;

$debug = 0;
$upload = 1;

$info['data']['path'] = "/Applications/MxSrvs/www/vipkwd-utils/examples/opencv/example/upload/v3DUJX8IMqIXiRBO.mp4";

$xml_dir = __DIR__.'/../opencv-3.4.3/data/haarcascades_cuda/haarcascade_frontalface_alt.xml';

if(isset($_GET['viewPic'])){
    $pic = base64_decode(trim($_GET['viewPic']));
    Opencv::instance()
        ->faceDetect($pic, $xml_dir)
        ->drawBorder()
        ->sendPNG();
}

if(!empty(Http::request()->getFiles())){
    $info = File::upload([
        "upload_dir" => __DIR__.'/upload',
        "type" => 'mp4,webm'
    ]);
}

// Dev::dump([$info, Http::request()->getFiles()],1);


if( !isset($info['data']['path']) || !File::exists($info['data']['path'])){
    Http::response()->send('测试文件不存在');
}


$list = (new FFmpeg)->exportImages($info['data']['path'], 1, '', '0', 30);


$algorithm_opencv = [];
$cv_data = ['w'=>[],'h'=>[]];

$algorithm_data = [];
$algorithm_steps = [3,5,7,9,11,13,17, 19,23];
$algorithm_lens = count($algorithm_steps);
foreach($list['images'] as $k => $filename){

    $cv = Opencv::instance()->faceDetect($list['output'].'/'.$filename, $xml_dir)->data();
    $debug && $cv["pa"] = '<img src="./video.php?viewPic='.base64_encode($list['output'].'/'.$filename).'" width="200"/>';

    $cv_data['w'][] = $cv[0]['w'] ?? 0;

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


$cv_data = ['w'=>[],'h'=>[]];
foreach($algorithm_opencv as $k => $item){
    $cv_data['w'][] = $item[0]['w'] ?? 0;
    $cv_data['h'][] = $item[0]['h'] ?? 0;
}

// Dev::dump($algorithm_data);
//图片相似度 AVG
rsort($algorithm_data); //关联数值改索引数组了
unset($algorithm_data[0]);//去除最大值
array_pop($algorithm_data);//去除最小值


//去除面部 最大/最小值 （x,y）
$max = max($cv_data['w']);
$min = min($cv_data['w']);
$_max = $_min = 'true';
foreach($cv_data['w'] as $k => $val){
    //保证相同最大值时仅剔除一个
    if($val == $max && !empty($_max)){
        unset($cv_data['w'][$k]);
        unset($cv_data['h'][$k]);
        unset($_max);
    }
    //保证相同最小值时仅剔除一个
    if($val == $min && !empty($_min)){
        unset($cv_data['w'][$k]);
        unset($cv_data['h'][$k]);
        unset($_min);
    }
}

$algorithm_distance = floatval(bcdiv(array_sum($algorithm_data), count($algorithm_data), 4));

$result = [
    "img_pass" => ($algorithm_distance < 80 || $algorithm_distance > 95 ) ? "fail" : "passed",
    "img_pass_reason" => $algorithm_distance < 80 ? "活体动作幅度偏大(<80)" : ($algorithm_distance > 95 ? "活体动作幅度偏小(>95)" :'-'),
    "img_num" => $algorithm_distance,
    // "cv"  => $cv_data,
    "cv_size_avg" => [
        'w' => bcdiv(array_sum($cv_data['w']) / count($cv_data['w']) , 1),
        'h' => bcdiv(array_sum($cv_data['h']) / count($cv_data['h']) , 1),
    ],
];

$cv_out = array_filter($cv_data['w'],function($w) use($result){
    //检测宽度 小于  所有图片宽度平均值的 有几张
    return ($w < $result['cv_size_avg']['w'] || $result['cv_size_avg']['w'] == 0);
});
$result['cv_pass'] =  (count($cv_out) >= 3) ? "fail" : "passed";
$result['cv_pass_reason'] =  (count($cv_out) >= 3) ? "触发面部遗失阀值(>=3)" : "-";

//清理资源
File::delete($list['source']);
foreach($list['images'] as $filename){
    File::delete($list['output'].'/'.$filename);
}

$debug && Dev::dumper([$cv_out, $result]);

$debug && Dev::dumper([$algorithm_opencv, $algorithm_data, $list, $info, Http::request()->files]);


if(!$debug){
    $response = [
        "img" => $result['img_pass'],
        "cv" => $result['cv_pass'],
        'img_reason' => $result['img_pass_reason'],
        'cv_reason' => $result['cv_pass_reason'],

        "debug" => [
            // "info" => $info,
            "list" => $list['images'],
            // "xml_dir" => $xml_dir,
            "algorithm_opencv" => $algorithm_opencv,
            "algorithm_data" => $algorithm_data,
            "algorithm_lens" => $algorithm_lens,
            "cv_data" => $cv_data,
            "cv_x_distance" => $result['cv_size_avg']['w'],
            "cv_out" => array_values($cv_out),
            // "algorithm_steps" => $algorithm_steps,
            "algorithm_distance" => $algorithm_distance,
        ]
    ];
    unset(
        $info, $xml_dir, $list,
        $algorithm_opencv, $cv_data,
        $algorithm_data, $algorithm_steps, $algorithm_lens, $algorithm_distance,
        $result, $cv_out
    );

    // Dev::dump($response,1);
    Http::response()->send($response);
}

