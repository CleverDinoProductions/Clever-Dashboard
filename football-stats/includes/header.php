<!DOCTYPE html>
<html>
<head>
    <title>Football Stats Dashboard</title>
    <meta http-equiv="refresh" content="300">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="header">
    <h1>⚽ Football Stats Dashboard</h1>
</div>
<div class="tabs">
    <a href="?tab=table" class="tab <?= $tab == 'table' ? 'active' : '' ?>">Table</a>
    <a href="?tab=leeds" class="tab <?= $tab == 'leeds' ? 'active' : '' ?>">Leeds United</a>
    <a href="?tab=leeds2" class="tab <?= $tab == 'leeds2' ? 'active' : '' ?>">Leeds United Tracker</a>
    <a href="?tab=relegation" class="tab <?= $tab == 'relegation' ? 'active' : '' ?>">Relegation Battle</a>
</div>
<div class="container">
