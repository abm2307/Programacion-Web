<?php 
include('conexion.php');


$conn = connection();

$top_q = "SELECT Name, Continent, Population FROM Country ORDER BY Population DESC LIMIT 10";
$query = mysqli_query($conn,$top_q);

if (!$query) {
  die("Error en la consulta: " . mysqli_error($conn));
}

$por_Cont = "SELECT Continent, COUNT(*) AS Total
                       FROM Country
                       GROUP BY Continent
                       ORDER BY Total DESC";
$c_query = mysqli_query($conn,$por_Cont);                      
if (!$c_query) {
  die("Error en la consulta: " . mysqli_error($conn));
}

?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  
</head>
<body style="font-family:Arial;margin:24px;">
  <?php include 'nav.php'; ?>
<div>
  <h1 style="text-align: center;"> Consulta Base Datos World</h1>
 
  <p>Selecciona un módulo:</p>
  <ul>
   <li><a href="queries.php">Buscar País </a></li> 
   <li><a href="countries.php">Agregar País</a></li>
   <li><a href="modificar.php">Modificar y Eliminar Pais</a></li>
  </ul>
</div><br>

<div>

<h1 style="text-align: center;" >Estadísticas Generales</h1>

<h2>Top 10 países por población</h2>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;">
  <tr><th>País</th><th>Continente</th><th>Población</th></tr>
  <?php while($row = mysqli_fetch_assoc($query)): ?>
    <tr>
      <td><?= htmlspecialchars($row['Name'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($row['Continent'], ENT_QUOTES,'UTF-8') ?></td>
      <td><?= number_format((int)$row['Population']) ?></td>
    </tr>
  <?php endwhile; ?>
</table>

<h2>Número de Paises por Continente</h2>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;">
  <tr><th>Continente</th><th>Paises</th></tr>
  <?php while($row = mysqli_fetch_assoc($c_query)): ?>
    <tr>
      <td><?= htmlspecialchars($row['Continent'],ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= number_format((int)$row['Total']) ?></td>
    </tr>
  <?php endwhile; ?>
</table>

 </div><br>

</body>
</html>