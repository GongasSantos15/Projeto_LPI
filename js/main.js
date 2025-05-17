/*** Template JS ***/
(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 45) {
            $('.navbar').addClass('sticky-top shadow-sm');
        } else {
            $('.navbar').removeClass('sticky-top shadow-sm');
        }
    });
    
    
    // Dropdown on mouse hover
    const $dropdown = $(".dropdown");
    const $dropdownToggle = $(".dropdown-toggle");
    const $dropdownMenu = $(".dropdown-menu");
    const showClass = "show";
    
    $(window).on("load resize", function() {
        if (this.matchMedia("(min-width: 992px)").matches) {
            $dropdown.hover(
            function() {
                const $this = $(this);
                $this.addClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "true");
                $this.find($dropdownMenu).addClass(showClass);
            },
            function() {
                const $this = $(this);
                $this.removeClass(showClass);
                $this.find($dropdownToggle).attr("aria-expanded", "false");
                $this.find($dropdownMenu).removeClass(showClass);
            }
            );
        } else {
            $dropdown.off("mouseenter mouseleave");
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });


    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        center: true,
        margin: 24,
        dots: true,
        loop: true,
        nav : false,
        responsive: {
            0:{
                items:1
            },
            768:{
                items:2
            },
            992:{
                items:3
            }
        }
    });
    
})(jQuery);

// FUNÇÕES CRIADAS POR MIM
// Função para carregar os distritos
function carregarDistritos() {
    fetch('/lpi/Projeto_LPI/distritos.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('origem').innerHTML = data;
            document.getElementById('destino').innerHTML = data;
        })
        .catch(error => {
            console.error('Erro ao carregar distritos:', error);
        });
}

window.onload = carregarDistritos;

// Função para editar o nome do utilizador
document.addEventListener('DOMContentLoaded', function() {
    // Seleciona TODOS os elementos com a classe 'edit-icon'
    const editIcons = document.querySelectorAll('.edit-icon');
    // Seleciona os campos de input que queremos tornar editáveis
    const nameInput = document.getElementById('nome');
    const firstNameInput = document.getElementById('nomeProprio');
    // Seleciona o botão de guardar
    const saveButton = document.getElementById('saveButton');

    // Adiciona um listener de clique a CADA ícone de edição
    editIcons.forEach(function(icon) {
        icon.addEventListener('click', function() {
            // Verifica se os campos estão desativados (estado inicial)
            if (nameInput.disabled && firstNameInput.disabled) {
                // Habilita AMBOS os campos de input
                nameInput.disabled = false;
                firstNameInput.disabled = false;

                // Mostra o botão de guardar
                saveButton.style.display = 'block';

                // Opcional: Oculta todos os ícones de edição depois de habilitar os campos
                editIcons.forEach(function(editIcon) {
                    editIcon.style.display = 'none';
                });

                // Opcional: Foca no primeiro campo de input para facilitar a edição
                nameInput.focus();
            }
        });
    });

    // Pode adicionar aqui lógica adicional se quiser,
    // como desabilitar os campos novamente se o utilizador cancelar a edição.
});