<?php
include('conexion.php');
$conn = connection();
mysqli_set_charset($conn, 'utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }


$errors = [];
$q = trim($_GET['q'] ?? '');
$code = strtoupper(trim($_GET['code'] ?? ''));  // país seleccionado

$self = $_SERVER['PHP_SELF']; 

$results = [];
$selected = null;

/** 1) Buscar países por nombre (parcial o completo) */
if ($q !== '') {
  $sql = "SELECT Code, Name, Continent, Region, Population
          FROM Country
          WHERE Name LIKE ?
          ORDER BY Name
          LIMIT 200";

  $stmt = mysqli_prepare($conn, $sql);
  if (!$stmt) {
    $errors[] = "Error al preparar búsqueda: " . mysqli_error($conn);
  } else {
    $like = "%" . $q . "%";
    mysqli_stmt_bind_param($stmt, "s", $like);
    mysqli_stmt_execute($stmt);

    // SIN get_result: usamos bind_result
    mysqli_stmt_bind_result($stmt, $cCode, $cName, $cCont, $cReg, $cPop);
    while (mysqli_stmt_fetch($stmt)) {
      $results[] = [
        'Code' => $cCode,
        'Name' => $cName,
        'Continent' => $cCont,
        'Region' => $cReg,
        'Population' => $cPop
      ];
    }
    mysqli_stmt_close($stmt);
  }
}

/** 2) Cargar país seleccionado por Código */
if ($code !== '') {
  if (strlen($code) !== 3) {
    $errors[] = "Código inválido (debe tener 3 letras).";
  } else {
    $sql = "SELECT
              Code, Name, Continent, Region, SurfaceArea, IndepYear, Population,
              LifeExpectancy, GNP, GNPOld, LocalName, GovernmentForm, HeadOfState,
              Capital, Code2
            FROM Country
            WHERE Code = ?";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      $errors[] = "Error al preparar consulta de detalle: " . mysqli_error($conn);
    } else {
      mysqli_stmt_bind_param($stmt, "s", $code);
      mysqli_stmt_execute($stmt);

      mysqli_stmt_bind_result(
        $stmt,
        $dCode, $dName, $dCont, $dReg, $dSurface, $dIndep, $dPop,
        $dLife, $dGnp, $dGnpOld, $dLocal, $dGov, $dHead, $dCapital, $dCode2
      );

      if (mysqli_stmt_fetch($stmt)) {
        $selected = [
          'Code' => $dCode,
          'Name' => $dName,
          'Continent' => $dCont,
          'Region' => $dReg,
          'SurfaceArea' => $dSurface,
          'IndepYear' => $dIndep,
          'Population' => $dPop,
          'LifeExpectancy' => $dLife,
          'GNP' => $dGnp,
          'GNPOld' => $dGnpOld,
          'LocalName' => $dLocal,
          'GovernmentForm' => $dGov,
          'HeadOfState' => $dHead,
          'Capital' => $dCapital,
          'Code2' => $dCode2
        ];
      } else {
        $errors[] = "No se encontró el país con Code: $code";
      }

      mysqli_stmt_close($stmt);
    }
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Buscar país</title>
</head>
<body style="font-family:Arial;margin:24px;">
<?php if (file_exists('nav.php')) include 'nav.php'; ?>

<h1>Buscar país (world.sql)</h1>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?= h($e) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="get" action="<?= h($self) ?>" style="max-width:760px;">
  <label><b>Nombre del país</b> (completo o parcial):</label><br>
  <input
    name="q"
    value="<?= h($q) ?>"
    placeholder="Ej: Mex, United, Argen..."
    style="width:100%;padding:8px;"
  >
  <br><br>

  <button type="submit">Buscar</button>
  <a href="<?= h($self) ?>" style="margin-left:10px;">Limpiar</a>
</form>

<?php if ($q !== ''): ?>
  <hr>
  <h2>Resultados (<?= count($results) ?>)</h2>

  <?php if (!$results): ?>
    <p>No se encontraron países con ese texto.</p>
  <?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:980px;">
      <tr>
        <th>Seleccionar</th>
        <th>Code</th>
        <th>Nombre</th>
        <th>Continente</th>
        <th>Región</th>
        <th>Población</th>
      </tr>

      <?php foreach ($results as $row): ?>
        <tr>
          <td>
            <!-- IMPORTANTE: link al MISMO archivo para evitar 404 -->
            <a href="<?= h($self) ?>?q=<?= urlencode($q) ?>&code=<?= h($row['Code']) ?>">Ver</a>
          </td>
          <td><?= h($row['Code']) ?></td>
          <td><?= h($row['Name']) ?></td>
          <td><?= h($row['Continent']) ?></td>
          <td><?= h($row['Region']) ?></td>
          <td><?= h(number_format((int)$row['Population'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>

<?php if ($selected): ?>
  <hr>
  <h2>País seleccionado: <?= h($selected['Name']) ?> (<?= h($selected['Code']) ?>)</h2>

  <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:980px;">
    <?php foreach ($selected as $k => $v): ?>
      <tr>
        <th style="text-align:left;width:240px;"><?= h($k) ?></th>
        <td><?= h($v) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

</body>
</html>
