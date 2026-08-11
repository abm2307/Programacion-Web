$(document).ready(function(){

    // Efecto de carga
    $(".card").hide().fadeIn(1500);

    // Altura de barras (datos simulados)
    $(".barra").each(function(){

        let valor = $(this).data("valor");

        $(this).animate({
            height: valor + "px"
        },1000);

    });

    // Evento CLICK
    $(".barra").click(function(){

        let valor = $(this).data("valor");

        $("#detalle").html(
            "Ventas registradas: $" +
            (valor * 1000).toLocaleString()
        );

    });

    // Evento HOVER
    $(".barra").hover(
        function(){

            let valor = $(this).data("valor");

            $("#detalle").html(
                "Mes seleccionado: " + valor + " unidades vendidas"
            );

        },
        function(){

            $("#detalle").html(
                "Pasa el mouse sobre una barra o haz clic."
            );

        }
    );

    // slideToggle
    $("#btnGrafica").click(function(){

        $(".grafica-container").slideToggle();

    });

    // keyup
    $("#busqueda").keyup(function(){

        let texto = $(this).val();

        $("#mensajeTeclado").text(
            "Buscando: " + texto
        );

    });

    // keydown
    $("#busqueda").keydown(function(event){

        $("#mensajeTeclado").html(
            "Tecla presionada: " +
            event.key
        );

    });

});
// Efecto al cargar la tabla
$(".empleados-container").hide().fadeIn(2000);

// Evento click sobre empleados
$(".fila-empleado").click(function(){

    let nombre = $(this).find("td:eq(0)").text();
    let ventas = $(this).find("td:eq(1)").text();
    let cumplimiento = $(this).find("td:eq(2)").text();

    $("#detalleEmpleado").html(
        "<strong>Empleado:</strong> " + nombre +
        "<br><strong>Ventas:</strong> " + ventas +
        "<br><strong>Cumplimiento:</strong> " + cumplimiento
    );

});

// Evento hover sobre empleados
$(".fila-empleado").hover(
    function(){
        $(this).css("background","#d6eaf8");
    },
    function(){
        $(this).css("background","");
    }
);
