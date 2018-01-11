<!doctype html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>获取位置</title>
    <style>
        *{ padding: 0; margin:0; }
        #box{ width: 500px; height: 300px; }
        .icon{ 
            position: absolute; left: 0; top: 0; width: 20px; height: 20px; 
            background: url("img/favicon.png") no-repeat; 
            background-size: cover; -background-color: red; 
        }
    </style>
    </head>
    <body>
    <div id="box">
    <img src="{{$imgUrl}}" alt="" />
    </div>
    <form action="./login" method="post">
        {!! csrf_field() !!}            
        <input id="location" type="text" name="randCode" />
        <input type="hidden" name="comeFrom" @if (session('comeFrom'))value="{{session('comeFrom')}}" @else value="" @endif />
        <input id="btn" type="submit" value="提交">
    </form>

<script>
var oBox = document.getElementById("box");
var oBtn = document.getElementById("btn");
var oLocation = document.getElementById("location");
var position = "";
oBox.onclick = function(ev){
    // console.log(ev);
    var x = ev.clientX;
    var y = ev.clientY;

    if(x>=0 && y-30>=0){
        position+=(x)+","+(y-30)+",";
        console.log(position);
        var oSpan = document.createElement("span");
        oSpan.className = "icon";
        // var oSpan = document.appendChild(span);
        oSpan.style.left = x + "px";
        oSpan.style.top = y + "px";
        oBox.appendChild(oSpan);
        oLocation.value = position;
    }
}
</script>
</body>
</html>
