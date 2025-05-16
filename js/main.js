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

// Funções criadas por mim
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
    const editIcon = document.getElementById('editIcon');
    const nameInput = document.getElementById('nome');
    const saveButton = document.getElementById('saveButton');

    // Make the edit icon toggle the input and button visibility/state
    editIcon.addEventListener('click', function() {
        if (nameInput.disabled) {
            // Enable input
            nameInput.disabled = false;
            // Show save button
            saveButton.style.display = 'block';
            // Hide edit icon (optional, can change icon too)
            editIcon.style.display = 'none'; // or editIcon.classList.add('d-none');
            // Set focus to the input field
            nameInput.focus();
        }
    });
});

document.getElementById('editIcon').addEventListener('click', function() {
    // Habilita o campo de input do nome
    document.getElementById('nome').disabled = false;
    // Mostra o botão de guardar
    document.getElementById('saveButton').style.display = 'block';
    // Opcional: Foca no campo de input para facilitar a edição
    document.getElementById('nome').focus();
});