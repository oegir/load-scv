<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Insert title here</title>
<script type="text/javascript" src="./assets/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="./assets/script.js"></script>
<link rel="stylesheet" href="./assets/style.css">
</head>
<body>
	<form id="myForm" action="index.php" enctype="multipart/form-data">
		<label><span>Файл импорта:</span><input id="import_data" type="file" name="import_data" /></label>
		<input type="hidden" name="action" value="parallel_import" />
		<button type="submit">Импорт</button>
	</form>
	<div id="resultContainer"></div>
</body>
</html>