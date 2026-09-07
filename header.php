<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        <?php include(__DIR__ . '/admin_style.php'); ?>
    </style>
</head>
<body>
    <?php include(__DIR__ . '/sidebar.php'); ?>
    <?php include(__DIR__ . '/popup.php'); ?>

<script>
(function(){
    function findMain(doc){
        return doc.querySelector('.main-content') || doc.querySelector('.main') || doc.querySelector('.content') || doc.querySelector('main');
    }
    document.addEventListener('click', function(e){
        var a = e.target.closest('.nav-link');
        if(!a) return;
        var href = a.getAttribute('href');
        if(!href) return;
        if(a.dataset && a.dataset.noAjax) return;
        if(href.indexOf('http') === 0 || href.indexOf('//') === 0) return;
        e.preventDefault();
        fetch(href, {credentials: 'same-origin'})
            .then(function(res){ return res.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newMain = findMain(doc);
                var curMain = findMain(document);
                if(newMain && curMain){
                    curMain.innerHTML = newMain.innerHTML;
                    history.pushState({ajax:true}, '', href);
                } else {
                    window.location = href;
                }
            }).catch(function(){ window.location = href; });
    });
    window.addEventListener('popstate', function(){
        var href = location.pathname + location.search;
        fetch(href, {credentials: 'same-origin'})
            .then(function(res){ return res.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newMain = findMain(doc);
                var curMain = findMain(document);
                if(newMain && curMain) curMain.innerHTML = newMain.innerHTML;
            });
    });
})();
</script>
