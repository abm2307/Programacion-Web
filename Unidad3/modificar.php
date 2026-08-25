<?php
include('conexion.php');
$conn = connection();
mysqli_set_charset($conn, 'utf8mb4');

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$continents = ["Africa","Antarctica","Asia","Europe","North America","Oceania","South America"];

$errors = [];
$success = "";

// Helpers para obtener un país por Code
function getCountryByCode($conn, $code) {
  $sql = "SELECT * FROM Country WHERE Code = ?";
  $stmt = mysqli_prepare($conn, $sql);
  if(!$stmt) return [null, "Error prepare: " . mysqli_error($conn)];
  mysqli_stmt_bind_param($stmt, "s", $code);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = $res ? mysqli_fetch_assoc($res) : null;
  mysqli_stmt_close($stmt);
  if (!$row) return [null, "No existe el país con Code: " . $code];
  return [$row, null];
}

// 1) Acciones POST: update / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // DELETE
  if ($action === 'delete') {
    $code = strtoupper(trim($_POST['Code'] ?? ''));
    if (strlen($code) !== 3) {
      $errors[] = "Code inválido para borrar.";
    } else {
      $stmt = mysqli_prepare($conn, "DELETE FROM Country WHERE Code = ?");
      if (!$stmt) {
        $errors[] = "Error prepare delete: " . mysqli_error($conn);
      } else {
        mysqli_stmt_bind_param($stmt, "s", $code);
        if (mysqli_stmt_execute($stmt)) {
          $success = "✅ País eliminado: $code";
        } else {
          $errors[] = "Error al eliminar ($code): " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
      }
    }
  }

  // UPDATE
  if ($action === 'update') {
    $data = [
      'Code' => strtoupper(trim($_POST['Code'] ?? '')),
      'Name' => trim($_POST['Name'] ?? ''),
      'Continent' => trim($_POST['Continent'] ?? ''),
      'Region' => trim($_POST['Region'] ?? ''),
      'SurfaceArea' => trim($_POST['SurfaceArea'] ?? ''),
      'IndepYear' => trim($_POST['IndepYear'] ?? ''),
      'Population' => trim($_POST['Population'] ?? ''),
      'LifeExpectancy' => trim($_POST['LifeExpectancy'] ?? ''),
      'GNP' => trim($_POST['GNP'] ?? ''),
      'GNPOld' => trim($_POST['GNPOld'] ?? ''),
      'LocalName' => trim($_POST['LocalName'] ?? ''),
      'GovernmentForm' => trim($_POST['GovernmentForm'] ?? ''),
      'HeadOfState' => trim($_POST['HeadOfState'] ?? ''),
      'Capital' => trim($_POST['Capital'] ?? ''),
      'Code2' => strtoupper(trim($_POST['Code2'] ?? '')),
    ];

    // Validaciones mínimas
    if (strlen($data['Code']) !== 3) $errors[] = "Code debe tener 3 letras.";
    if ($data['Name'] === '') $errors[] = "Name es obligatorio.";
    if (!in_array($data['Continent'], $continents, true)) $errors[] = "Continent inválido.";
    if ($data['Region'] === '') $errors[] = "Region es obligatorio.";
    if ($data['Code2'] === '' || strlen($data['Code2']) !== 2) $errors[] = "Code2 debe tener 2 letras.";
    if ($data['Population'] === '' || !ctype_digit($data['Population'])) $errors[] = "Population debe ser entero (>= 0).";
    if ($data['SurfaceArea'] === '' || !is_numeric($data['SurfaceArea'])) $errors[] = "SurfaceArea debe ser numérico.";
    if ($data['GNP'] === '' || !is_numeric($data['GNP'])) $errors[] = "GNP debe ser numérico.";
    if ($data['GNPOld'] !== '' && !is_numeric($data['GNPOld'])) $errors[] = "GNPOld debe ser numérico o vacío.";
    if ($data['LifeExpectancy'] !== '' && !is_numeric($data['LifeExpectancy'])) $errors[] = "LifeExpectancy debe ser numérico o vacío.";
    if ($data['IndepYear'] !== '' && !preg_match('/^\d{1,4}$/', $data['IndepYear'])) $errors[] = "IndepYear debe ser año numérico o vacío.";
    if ($data['Capital'] !== '' && !ctype_digit($data['Capital'])) $errors[] = "Capital debe ser ID numérico o vacío.";

    if (!$errors) {
      $surfaceArea = (float)$data['SurfaceArea'];
      $indepYear   = ($data['IndepYear'] === '') ? null : (int)$data['IndepYear'];
      $population  = (int)$data['Population'];
      $lifeExp     = ($data['LifeExpectancy'] === '') ? null : (float)$data['LifeExpectancy'];
      $gnp         = (float)$data['GNP'];
      $gnpOld      = ($data['GNPOld'] === '') ? null : (float)$data['GNPOld'];
      $headState   = ($data['HeadOfState'] === '') ? null : $data['HeadOfState'];
      $capital     = ($data['Capital'] === '') ? null : (int)$data['Capital'];

      $sql = "UPDATE Country SET
                Name = ?,
                Continent = ?,
                Region = ?,
                SurfaceArea = ?,
                IndepYear = ?,
                Population = ?,
                LifeExpectancy = ?,
                GNP = ?,
                GNPOld = ?,
                LocalName = ?,
                GovernmentForm = ?,
                HeadOfState = ?,
                Capital = ?,
                Code2 = ?
              WHERE Code = ?";

      $stmt = mysqli_prepare($conn, $sql);
      if (!$stmt) {
        $errors[] = "Error prepare update: " . mysqli_error($conn);
      } else {
        $types = "sssdiidddsssiss"; // 15 params

        mysqli_stmt_bind_param(
          $stmt,
          $types,
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
          $data['Code2'],
          $data['Code']
        );

        if (mysqli_stmt_execute($stmt)) {
          $success = " País actualizado: " . $data['Code'];
        } else {
          $errors[] = "Error al actualizar: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
      }
    }
  }
}

// 2) GET: buscar / editar
$selectedContinent = trim($_GET['continent'] ?? '');
$nameQ = trim($_GET['name'] ?? '');
$doSearch = isset($_GET['search']);

$editCode = strtoupper(trim($_GET['edit'] ?? ''));
$editing = ($editCode !== '');

$editData = null;
if ($editing) {
  [$editData, $err] = getCountryByCode($conn, $editCode);
  if ($err) $errors[] = $err;
}

// Buscar resultados por CONTINENTE
$results = [];
if ($doSearch && !$editing) {
  if ($selectedContinent === '') $errors[] = "Selecciona un continente para buscar.";
  if ($nameQ === '') $errors[] = "Escribe el nombre (o parte del nombre) del país.";

  if (!$errors) {
    $sql = "SELECT Code, Name, Continent, Region, Population
            FROM Country
            WHERE Continent = ?
              AND Name LIKE ?
            ORDER BY Name
            LIMIT 200";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
      $errors[] = "Error prepare search: " . mysqli_error($conn);
    } else {
      $like = "%" . $nameQ . "%";
      mysqli_stmt_bind_param($stmt, "ss", $selectedContinent, $like);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $results[] = $row;
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
  <title>Buscar / Editar / Borrar país</title>
</head>
<body style="font-family:Arial;margin:24px;">
<?php if (file_exists('nav.php')) include 'nav.php'; ?>

<h1>Países: Buscar por Continente, modificar o borrar</h1>

<?php if ($success): ?>
  <p style="color:green;"><b><?= h($success) ?></b></p>
<?php endif; ?>

<?php if ($errors): ?>
  <ul style="color:red;">
    <?php foreach ($errors as $e): ?>
      <li><?= h($e) ?></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<hr>

<h2>Buscar país</h2>
<form method="get" style="max-width:760px;">
  <input type="hidden" name="search" value="1">

  <div>
    <label><b>Continente</b>:</label><br>
    <select name="continent" required style="min-width:360px;">
      <option value="">-- Selecciona continente --</option>
      <?php foreach ($continents as $c): ?>
        <option value="<?= h($c) ?>" <?= ($selectedContinent === $c ? 'selected' : '') ?>>
          <?= h($c) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div><br>

  <div>
    <label><b>Nombre</b> (parcial o completo):</label><br>
    <input name="name" value="<?= h($nameQ) ?>" placeholder="Ej: Mex" style="width:100%;" required>
  </div><br>

  <button type="submit">Buscar</button>
</form>

<?php if ($doSearch && !$editing): ?>
  <h3>Resultados</h3>
  <?php if (!$results): ?>
    <p>No se encontraron países con esos filtros.</p>
  <?php else: ?>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%;max-width:980px;">
      <tr>
        <th>Code</th><th>Nombre</th><th>Continente</th><th>Región</th><th>Población</th><th>Acciones</th>
      </tr>
      <?php foreach ($results as $row): ?>
        <tr>
          <td><?= h($row['Code']) ?></td>
          <td><?= h($row['Name']) ?></td>
          <td><?= h($row['Continent']) ?></td>
          <td><?= h($row['Region']) ?></td>
          <td><?= h(number_format((int)$row['Population'])) ?></td>
          <td>
            <a href="?edit=<?= h($row['Code']) ?>">✏️ Editar</a>

            <form method="post" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar <?= h($row['Code']) ?> - <?= h($row['Name']) ?>?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="Code" value="<?= h($row['Code']) ?>">
              <button type="submit">🗑️ Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>

<?php if ($editing && $editData): ?>
  <hr>
  <h2>Editar país: <?= h($editData['Code']) ?> - <?= h($editData['Name']) ?></h2>
  <p><a href="country_manage.php">⬅️ Volver a búsqueda</a></p>

  <form method="post" style="max-width:900px;">
    <input type="hidden" name="action" value="update">

    <div>
      <label><b>Code</b> (no editable):</label><br>
      <input name="Code" value="<?= h($editData['Code']) ?>" readonly>
    </div><br>

    <div>
      <label><b>Name</b>:</label><br>
      <input name="Name" value="<?= h($editData['Name']) ?>" required style="width:100%;">
    </div><br>

    <div>
      <label><b>Continent</b>:</label><br>
      <select name="Continent" required>
        <?php foreach ($continents as $c): ?>
          <option value="<?= h($c) ?>" <?= ($editData['Continent']===$c?'selected':'') ?>><?= h($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div><br>

    <div>
      <label><b>Region</b>:</label><br>
      <input name="Region" value="<?= h($editData['Region']) ?>" required style="width:100%;">
    </div><br>

    <div>
      <label><b>Population</b>:</label><br>
      <input type="number" name="Population" min="0" value="<?= h($editData['Population']) ?>" required>
    </div><br>

    <hr>
    <h3>Opcionales</h3>

    <div>
      <label>Code2:</label><br>
      <input name="Code2" maxlength="2" value="<?= h($editData['Code2']) ?>" required>
    </div><br>

    <div>
      <label>LocalName:</label><br>
      <input name="LocalName" value="<?= h($editData['LocalName']) ?>" style="width:100%;">
    </div><br>

    <div>
      <label>GovernmentForm:</label><br>
      <input name="GovernmentForm" value="<?= h($editData['GovernmentForm']) ?>" style="width:100%;">
    </div><br>

    <div>
      <label>HeadOfState:</label><br>
      <input name="HeadOfState" value="<?= h($editData['HeadOfState']) ?>" style="width:100%;">
    </div><br>

    <div>
      <label>Capital (ID City, opcional):</label><br>
      <input type="number" name="Capital" min="0" value="<?= h($editData['Capital']) ?>">
    </div><br>

    <div>
      <label>SurfaceArea:</label><br>
      <input name="SurfaceArea" value="<?= h($editData['SurfaceArea']) ?>">
    </div><br>

    <div>
      <label>IndepYear:</label><br>
      <input name="IndepYear" value="<?= h($editData['IndepYear']) ?>">
    </div><br>

    <div>
      <label>LifeExpectancy:</label><br>
      <input name="LifeExpectancy" value="<?= h($editData['LifeExpectancy']) ?>">
    </div><br>

    <div>
      <label>GNP:</label><br>
      <input name="GNP" value="<?= h($editData['GNP']) ?>">
    </div><br>

    <div>
      <label>GNPOld:</label><br>
      <input name="GNPOld" value="<?= h($editData['GNPOld']) ?>">
    </div><br>

    <button type="submit">Guardar cambios</button>
  </form>

  <form method="post" style="margin-top:14px;" onsubmit="return confirm('¿Seguro que deseas eliminar <?= h($editData['Code']) ?> - <?= h($editData['Name']) ?>?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="Code" value="<?= h($editData['Code']) ?>">
    <button type="submit">🗑️ Eliminar este país</button>
  </form>

<?php endif; ?>

</body>
</html>
