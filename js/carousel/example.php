<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Documento sin t&iacute;tulo</title>
    
    <link rel="stylesheet" type="text/css" href="icarousel.css">
    
	<script type="text/javascript" src="mootools.js"></script>  
	<script type="text/javascript" src="icarousel.js"></script>
    <script type="text/javascript" src="shCore.js"></script>
	
	<script type="text/javascript">
    window.addEvent("domready", function() {
        dp.SyntaxHighlighter.HighlightAll("usage");
        
        new iCarousel("example_3_content", {
            idPrevious: "example_3_previous",
            idNext: "example_3_next",
            idToggle: "undefined",
            item: {
                klass: "example_3_item",
                size: 86
            },
            animation: {
                duration: 400,
                amount: 1
            }
        });
    });
    </script>
</head>
<body>
    <div id="container_bd">
        <div id="example_3">
            <ul id="example_3_content">  
                <li class="example_3_item"><a href="#"><img src="img/1.jpg" alt="flower 1" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/2.jpg" alt="flower 2" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/3.jpg" alt="flower 3" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/4.jpg" alt="flower 4" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/5.jpg" alt="flower 5" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/6.jpg" alt="flower 6" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/7.jpg" alt="flower 7" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/8.jpg" alt="flower 8" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/9.jpg" alt="flower 9" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/A.jpg" alt="flower A" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/B.jpg" alt="flower B" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/C.jpg" alt="flower C" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/D.jpg" alt="flower D" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/E.jpg" alt="flower E" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/F.jpg" alt="flower F" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/G.jpg" alt="flower G" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/H.jpg" alt="flower H" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/I.jpg" alt="flower I" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/J.jpg" alt="flower J" /></a></li>  
                <li class="example_3_item"><a href="#"><img src="img/K.jpg" alt="flower K" /></a></li>  
            </ul>  
            <div id="example_3_frame">  
                <img id="example_3_previous" src="img/ex3_previous.gif" alt="move previous" />  
                <img id="example_3_next" src="img/ex3_next.gif" alt="move next" />  
            </div>
        </div>
    </div>
</body>
</html>