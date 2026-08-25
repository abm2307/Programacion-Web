<?php
include('conexion.php'); // Funcion de Conexión a Base de Datos()

$conn = connection();
if (!$conn) {
  die("Error de conexión a MySQL.");
}
mysqli_set_charset($conn, 'utf8mb4');

$continents = ["Africa","Antarctica","Asia","Europe","North America","Oceania","South America"];

$errors = [];
$success = false;

$data = [
  'Code' => '',
  'Name' => '',
  'Continent' => '',
  'Region' => '',
  'SurfaceArea' => '0.00',
  'IndepYear' => '',
  'Population' => '0',
  'LifeExpectancy' => '',
  'GNP' => '0.00',
  'GNPOld' => '',
  'LocalName' => '',
  'GovernmentForm' => '',
  'HeadOfState' => '',
  'Capital' => '',
  'Code2' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  foreach ($data as $k => $_) {
    $data[$k] = trim($_POST[$k] ?? '');
  }

  // Normalización
  $data['Code']  = strtoupper($data['Code']);
  $data['Code2'] = strtoupper($data['Code2']);

  // Validaciones mínimas
  if (strlen($data['Code']) !== 3) $errors[] = "El campo Code debe tener 3 letras (ej. MEX).";
  if ($data['Name'] === '') $errors[] = "El campo Name es obligatorio.";
  if (!in_array($data['Continent'], $continents, true)) $errors[] = "Selecciona un Continent válido.";
  if ($data['Region'] === '') $errors[] = "El campo Region es obligatorio.";
  if ($data['Code2'] === '' || strlen($data['Code2']) !== 2) $errors[] = "El campo Code2 debe tener 2 letras (ej. MX).";

  // Defaults útiles
  if ($data['LocalName'] === '') $data['LocalName'] = $data['Name'];
  if ($data['GovernmentForm'] === '') $data['GovernmentForm'] = 'Unknown';

  // Numéricos
  if ($data['Population'] === '' || !ctype_digit($data['Population'])) $errors[] = "Population debe ser entero (>= 0).";
  if ($data['SurfaceArea'] === '' || !is_numeric($data['SurfaceArea'])) $errors[] = "SurfaceArea debe ser numérico.";
  if ($data['GNP'] === '' || !is_numeric($data['GNP'])) $errors[] = "GNP debe ser numérico.";
  if ($data['GNPOld'] !== '' && !is_numeric($data['GNPOld'])) $errors[] = "GNPOld debe ser numérico o vacío.";
  if ($data['LifeExpectancy'] !== '' && !is_numeric($data['LifeExpectancy'])) $errors[] = "LifeExpectancy debe ser numérico o vacío.";

  if ($data['IndepYear'] !== '' && !preg_match('/^\d{1,4}$/', $data['IndepYear'])) $errors[] = "IndepYear debe ser un año numérico o vacío.";
  if ($data['Capital'] !== '' && !ctype_digit($data['Capital'])) $errors[] = "Capital debe ser un ID numérico de City o vacío.";

  // INSERT
  if (!$errors) {
    // NULLs para opcionales
    $indepYear = ($data['IndepYear'] === '') ? null : (int)$data['IndepYear'];
    $lifeExp   = ($data['LifeExpectancy'] === '') ? null : (float)$data['LifeExpectancy'];
    $gnpOld    = ($data['GNPOld'] === '') ? null : (float)$data['GNPOld'];
    $headState = ($data['HeadOfState'] === '') ? null : $data['HeadOfState'];
    $capital   = ($data['Capital'] === '') ? null : (int)$data['Capital'];

    // Casts recomendados
    $surfaceArea = (float)$data['SurfaceArea'];
    $population  = (int)$data['Population'];
    $gnp         = (float)$data['GNP'];

    $sql = "INSERT INTO Country
      (Code, Name, Continent, Region, SurfaceArea, IndepYear, Population, LifeExpectancy,
       GNP, GNPOld, LocalName, GovernmentForm, HeadOfState, Capital, Code2)
      VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      $errors[] = "Error al preparar statement: " . mysqli_error($conn);
    } else {
      $types = "ssssdiidddsssis"; // 15 parámetros, SIN espacios

      mysqli_stmt_bind_param(
        $stmt,
        $types,
        $data['Code'],
        $data['Name'],
        $data['Continent'],
        $data['Region'],
        $surfaceArea,
        $indepYear,
        $population,
        $lifeExp,
        $gnp,
        $gnpOld,
        $data['LocalName'],
        $data['GovernmentForm'],
        $headState,
        $capital,
        $data['Code2']
      );

      if (mysqli_stmt_execute($stmt)) {
        $success = true;

        // reset básico
        $data['Code'] = $data['Name'] = $data['Region'] = $data['Code2'] = '';
        $data['Continent'] = '';
        $data['Population'] = '0';
      } else {
        $errors[] = "Error al insertar: " . mysqli_stmt_error($stmt);
      }

      mysqli_stmt_close($stmt);
    }
  }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Agregar país</title>
</head>
<body style="font-family:Arial;margin:24px;">
<?php include 'nav.php'; ?>

<h1>Agregar país (Country)</h1>
<p><a href="index.php">⬅️ Volver a Menu Principal</a></p>

<?php if ($success): ?>
  <p style="color:green;"><b>✅ País agregado correctamente.</b></p>
<?php endif; ?>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?= h($e) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<form method="post" style="max-width:720px;">
  <div>
    <label><b>Code</b> (3 letras, ej. MEX):</label><br>
    <input name="Code" maxlength="3" value="<?= h($data['Code']) ?>" required>
  </div><br>

  <div>
    <label><b>Name</b>:</label><br>
    <input name="Name" value="<?= h($data['Name']) ?>" required style="width:100%;">
  </div><br>

  <div>
    <label><b>Continent</b>:</label><br>
    <select name="Continent" required>
      <option value="">-- Selecciona --</option>
      <?php foreach ($continents as $c): ?>
        <option value="<?= h($c) ?>" <?= $data['Continent']===$c?'selected':'' ?>><?= h($c) ?></option>
      <?php endforeach; ?>
    </select>
  </div><br>

  <div>
    <label><b>Region</b> (texto, ej. Central America):</label><br>
    <input name="Region" value="<?= h($data['Region']) ?>" required style="width:100%;">
  </div><br>

  <div>
    <label><b>Population</b>:</label><br>
    <input type="number" name="Population" min="0" value="<?= h($data['Population']) ?>" required>
  </div><br>

  <hr>
  <h3>Campos adicionales (opcionales)</h3>

  <div>
    <label>Code2 (2 letras, ej. MX):</label><br>
    <input name="Code2" maxlength="2" value="<?= h($data['Code2']) ?>" required>
  </div><br>

  <div>
    <label>LocalName:</label><br>
    <input name="LocalName" value="<?= h($data['LocalName']) ?>" style="width:100%;">
  </div><br>

  <div>
    <label>GovernmentForm:</label><br>
    <input name="GovernmentForm" value="<?= h($data['GovernmentForm']) ?>" style="width:100%;">
  </div><br>

  <div>
    <label>HeadOfState:</label><br>
    <input name="HeadOfState" value="<?= h($data['HeadOfState']) ?>" style="width:100%;">
  </div><br>

  <div>
    <label>Capital (ID de City, opcional):</label><br>
    <input type="number" name="Capital" min="0" value="<?= h($data['Capital']) ?>">
  </div><br>

  <div>
    <label>SurfaceArea:</label><br>
    <input name="SurfaceArea" value="<?= h($data['SurfaceArea']) ?>">
  </div><br>

  <div>
    <label>IndepYear (año):</label><br>
    <input name="IndepYear" value="<?= h($data['IndepYear']) ?>">
  </div><br>

  <div>
    <label>LifeExpectancy:</label><br>
    <input name="LifeExpectancy" value="<?= h($data['LifeExpectancy']) ?>">
  </div><br>

  <div>
    <label>GNP:</label><br>
    <input name="GNP" value="<?= h($data['GNP']) ?>">
  </div><br>

  <div>
    <label>GNPOld:</label><br>
    <input name="GNPOld" value="<?= h($data['GNPOld']) ?>">
  </div><br>

  <button type="submit">Guardar país</button>
</form>

</body>
</html>
