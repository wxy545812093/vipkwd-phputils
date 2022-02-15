<?php
    $list = file("./address.dat");
    $list = array_splice($list, 970);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>地址智能解析</title>
    <script src="./jquery.min.js"></script>
    <style>
        *{margin:0;padding:0}
        .container,.block-container{
            display: -webkit-flex;
            display: flex;
            width: 100%;
            line-height: 34px;
            /* justify-content: space-between; */
            /* align-items: stretch; */
            /* flex-wrap: nowrap; */
        }
        
        .container .block{
            height: auto;
            width: 50%;
        }
        .container .block:nth-of-type(1){
            /* background: green; */
            padding: 2rem 3rem;
            height: 500px;
            overflow-y:auto;
        }
        .container .block:nth-of-type(2){
            background: #eee;
        }
        .container .block li{
            cursor:pointer;
        }
        .container .block li:hover{
            color: #00f;
            text-decoration: underline;
        }
        .block-container{
            flex-wrap: wrap;
            /* align-content: flex-start; */
        }
        .block-container .item{
            height: auto;
            width: 100%;
            display: inline-flex;
        }
        .block-container .item > span{
            width:90px;
            text-align: right;
            display: block;
            color:#676767
        }
        hr{
            border-top: 1px dotted #d8d8d8;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container">
        <div class="block">
            <ol>
                <?php foreach($list as $address){ ?>
                <li><?php echo $address; ?></li>
                <?php } ?>
            </ol>
        </div>
        <div class="block">
            <div class="block-container"></div>
            <pre id="result">
            </pre>
        </div>
    </div>


    <script>
        let callback = (res) => {
            let html = `
            <div class="item"><span>省份：</span>${res.province||'-'}</div>
            <div class="item"><span>省份编码：</span>${res.provinceCode||'-'}</div>
            <div class="item"><span>城市：</span>${res.city||'-'}</div>
            <div class="item"><span>城市编码：</span>${res.cityCode||'-'}</div>
            <div class="item"><span>区县：</span>${res.county||'-'}</div>
            <div class="item"><span>区县编码：</span>${res.countyCode||'-'}</div>
            <div class="item"><span>街道：</span>${res.street||'-'}</div>
            <div class="item"><span>街道编码：</span>${res.streetCode||'-'}</div>
            <div class="item"><span>乡镇级别：</span>${res.townstreet ? "是" : "-"}</div>
            <div class="item"><span>邮编：</span>${res.zipCode ||'-'}</div>
            <div class="item"><span>备注：</span>${res.remark||'-'}</div>
            <div class="item"><span>详细地址：</span>${res.address||'-'}</div>
            <hr/>
            <div class="item"><span>手机号码：</span>${res.phone ||'-'}</div>
            <div class="item"><span>固定电话：</span>${res.telphone ||'-'}</div>
            <div class="item"><span>证件号码：</span>${res.idn ||'-'}</div>
            <div class="item"><span>姓名：</span>${res.name ||'-'}</div>
            <hr/>
            <div class="item"><span>原始串：</span>${res.__text||'-'}</div>
            <!-- <div class="item"><span>原始串：</span>${res.doc||'-'}</div> -->
            `;
            $(".block-container").html(html);
        };

        $(function(){
            $("li").click(function(){
                
                // $.get("//dl.vipkwd.com/address/parse.php", { address: this.innerText, jsonp: "callback"}, ()=> void 0,'jsonp')
                $.get("./parse.php", { address: this.innerText, jsonp: "callback"}, ()=> void 0,'jsonp')
            })
        })
    </script>
</body>
</html>