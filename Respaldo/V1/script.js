$(function () {
  const salesData = [
    { month: "Ene", value: 184000 }, { month: "Feb", value: 221000 },
    { month: "Mar", value: 198000 }, { month: "Abr", value: 249000 },
    { month: "May", value: 232000 }, { month: "Jun", value: 278000 },
    { month: "Jul", value: 284650 }
  ];

  const inventory = [
    { icon: "A", product: "Audífonos Pro", category: "Tecnología", stock: 4, status: "Crítico" },
    { icon: "S", product: "Silla Ergonómica", category: "Oficina", stock: 8, status: "Bajo" },
    { icon: "M", product: "Monitor 27 pulgadas", category: "Tecnología", stock: 6, status: "Bajo" }
  ];

  const employees = [
    { initials: "AM", name: "Ana Martínez", role: "Ejecutiva de ventas", score: 98 },
    { initials: "CR", name: "Carlos Ruiz", role: "Operaciones", score: 96 },
    { initials: "SG", name: "Sofía García", role: "Atención al cliente", score: 94 }
  ];

  const viewText = {
    resumen: ["Resumen ejecutivo", "Vista general", "Revisa las métricas más importantes de ventas, existencias y desempeño del equipo."],
    ventas: ["Análisis de ventas", "Detalle de ventas", "Las ventas crecieron 12.4% respecto del periodo anterior. Julio presenta el mejor resultado del periodo analizado."],
    inventario: ["Control de inventario", "Estado del inventario", "Hay tres productos que requieren reposición. Los audífonos Pro tienen el nivel de existencias más bajo."],
    empleados: ["Rendimiento de empleados", "Desempeño del equipo", "El promedio del equipo es 92.8%. Ana Martínez lidera el periodo con una calificación de 98%."],
    pedidos: ["Seguimiento de pedidos", "Pedidos del periodo", "Se completaron 1,248 pedidos y existen 94 pendientes de procesamiento."],
    rendimiento: ["Rendimiento de empleados", "Desempeño del equipo", "El equipo mejoró 4.6% y mantiene un promedio general de 92.8%."],
  };

  function formatCurrency(value) {
    return new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN", maximumFractionDigits: 0 }).format(value);
  }

  function renderChart() {
    const max = 300000;
    const bars = salesData.map(item => `
      <div class="bar-group">
        <button class="bar" style="height:${(item.value / max) * 100}%" data-month="${item.month}" data-value="${item.value}" aria-label="${item.month}: ${formatCurrency(item.value)}"></button>
        <span class="bar-label">${item.month}</span>
      </div>`).join("");
    $("#salesChart").html(bars).hide().fadeIn(700);
  }

  function renderInventory(filter = "") {
    const normalized = filter.toLowerCase().trim();
    const filtered = inventory.filter(item => `${item.product} ${item.category} ${item.status}`.toLowerCase().includes(normalized));
    const rows = filtered.map(item => `
      <tr>
        <td><span class="product-cell"><i class="product-dot">${item.icon}</i>${item.product}</span></td>
        <td>${item.category}</td>
        <td class="${item.stock <= 4 ? "stock-low" : ""}">${item.stock} unidades</td>
        <td><span class="status ${item.status === "Crítico" ? "critical" : "low"}">${item.status}</span></td>
      </tr>`).join("");
    $("#inventoryTable").html(rows);
    $("#emptyMessage").toggle(filtered.length === 0);
  }

  function renderEmployees() {
    const content = employees.map(person => `
      <div class="employee">
        <span class="employee-avatar">${person.initials}</span>
        <div><p class="employee-name">${person.name}</p><span class="employee-role">${person.role}</span><div class="progress"><span style="width:${person.score}%"></span></div></div>
        <span class="score">${person.score}%</span>
      </div>`).join("");
    $("#employeeList").html(content).hide().fadeIn(650);
  }

  function showDetails(key) {
    const info = viewText[key] || viewText.resumen;
    $("#pageTitle").text(info[0]);
    $("#detailContent").html(`<h2>${info[1]}</h2><p>${info[2]}</p>`);
    if (!$("#viewDetails").is(":visible")) $("#viewDetails").slideToggle(280);
    $("#viewDetails")[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  renderChart();
  renderInventory();
  renderEmployees();
  $(".metric-card, .content-grid, .lower-grid").hide().each(function (index) {
    $(this).delay(index * 120).fadeIn(500);
  });

  // Evento keyup: filtra productos conforme se escribe.
  $("#searchInput").on("keyup", function () {
    renderInventory($(this).val());
    $(".inventory-panel").fadeOut(80).fadeIn(220);
  });

  // Evento keydown: atajos Ctrl+K, R y Escape.
  $(document).on("keydown", function (event) {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
      event.preventDefault();
      $("#searchInput").trigger("focus");
    }
    if (event.key.toLowerCase() === "r" && !$(event.target).is("input")) {
      event.preventDefault();
      $("#refreshButton").trigger("click");
    }
    if (event.key === "Escape") {
      $("#viewDetails").slideUp(220);
      $("#sidebar").removeClass("open");
      $("#menuButton").attr("aria-expanded", "false");
    }
  });

  // Evento click: navegación, tarjetas, periodos y actualización.
  $(".nav-item").on("click", function () {
    $(".nav-item").removeClass("active").removeAttr("aria-current");
    $(this).addClass("active").attr("aria-current", "page");
    showDetails($(this).data("view"));
    $("#sidebar").removeClass("open");
  });

  $(".metric-card").on("click keydown", function (event) {
    if (event.type === "click" || event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      showDetails($(this).data("detail"));
    }
  });

  $(".period-filter button").on("click", function () {
    $(".period-filter button").removeClass("selected");
    $(this).addClass("selected");
    $("#salesChart").fadeOut(140, function () { $(this).fadeIn(320); });
    $("#toast").text(`Vista actualizada a ${$(this).data("period")} días`).fadeIn(200).delay(1300).fadeOut(350);
  });

  $("#refreshButton").on("click", function () {
    $(".dashboard").addClass("loading");
    setTimeout(() => {
      const variation = Math.floor(Math.random() * 9000);
      $("#salesValue").text(formatCurrency(284650 + variation));
      $("#ordersValue").text((1248 + Math.floor(variation / 150)).toLocaleString("es-MX"));
      $("#lastUpdate").text(new Date().toLocaleString("es-MX", { dateStyle: "long", timeStyle: "short" }));
      $(".dashboard").removeClass("loading").hide().fadeIn(420);
      $("#toast").text("Datos actualizados correctamente").fadeIn(200).delay(1600).fadeOut(350);
    }, 650);
  });

  $("#menuButton").on("click", function () {
    const isOpen = $("#sidebar").toggleClass("open").hasClass("open");
    $(this).attr("aria-expanded", isOpen);
  });

  $("#closeDetails").on("click", () => $("#viewDetails").slideToggle(240));
  $(".text-button").on("click", function () { showDetails($(this).data("view-target")); });

  // Evento hover: muestra información de cada barra.
  $(document).on("mouseenter mousemove", ".bar", function (event) {
    $("#tooltip").text(`${$(this).data("month")}: ${formatCurrency($(this).data("value"))}`).css({ left: event.clientX + 12, top: event.clientY - 35 }).stop(true, true).fadeIn(120);
  }).on("mouseleave", ".bar", function () {
    $("#tooltip").stop(true, true).fadeOut(100);
  }).on("click", ".bar", function () {
    $(".bar").removeClass("active");
    $(this).addClass("active");
    $("#detailContent").html(`<h2>Ventas de ${$(this).data("month")}</h2><p>El total registrado fue de <strong>${formatCurrency($(this).data("value"))}</strong>. Haz clic en otra barra para comparar el resultado.</p>`);
    if (!$("#viewDetails").is(":visible")) $("#viewDetails").slideToggle(260);
  });

  $(".metric-card").hover(
    function () { $(this).find(".metric-icon").stop().animate({ opacity: .65 }, 120); },
    function () { $(this).find(".metric-icon").stop().animate({ opacity: 1 }, 120); }
  );
});
