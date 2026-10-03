document.addEventListener('DOMContentLoaded', () => {

  // HEADER FIJO
  const encabezadoPrincipal = document.getElementById('encabezadoPrincipal');
  const barraSuperior = document.querySelector('.encabezado__barra-superior');
  
  if (encabezadoPrincipal && barraSuperior) {
    const alturaBarra = barraSuperior.offsetHeight;
    window.addEventListener('scroll', () => {
      if (window.scrollY > alturaBarra) {
        encabezadoPrincipal.classList.add('encabezado__principal--fijo');
        document.body.style.paddingTop = `${encabezadoPrincipal.offsetHeight}px`;
      } else {
        encabezadoPrincipal.classList.remove('encabezado__principal--fijo');
        document.body.style.paddingTop = '0';
      }
    });
  }

  // LÓGICA DEL CARRUSEL DEL HERO (FADE CLÁSICO)
  const slidesHero = document.querySelectorAll('.portada__diapositiva');
  const puntosHero = document.querySelectorAll('.portada__punto');
  const btnAntHero = document.getElementById('btnAntHero');
  const btnSigHero = document.getElementById('btnSigHero');
  let slideActual = 0;

  if (slidesHero.length > 0 && btnAntHero && btnSigHero) {
    const mostrarSlide = (index) => {
      // Ocultar todos
      slidesHero.forEach(slide => slide.classList.remove('portada__diapositiva--activa'));
      puntosHero.forEach(punto => punto.classList.remove('portada__punto--activo'));

      // Mostrar el actual
      slidesHero[index].classList.add('portada__diapositiva--activa');
      if(puntosHero[index]) puntosHero[index].classList.add('portada__punto--activo');
    };

    btnSigHero.addEventListener('click', () => {
      slideActual = (slideActual === slidesHero.length - 1) ? 0 : slideActual + 1;
      mostrarSlide(slideActual);
    });

    btnAntHero.addEventListener('click', () => {
      slideActual = (slideActual === 0) ? slidesHero.length - 1 : slideActual - 1;
      mostrarSlide(slideActual);
    });
  }

  // --- SOLUCIÓN: VOLVEMOS A DEFINIR LA FUNCIÓN ACTIVARCARRUSEL ---
  const activarCarrusel = (idCarrusel, idBotonAnt, idBotonSig) => {
    const carrusel = document.getElementById(idCarrusel);
    const btnAnt = document.getElementById(idBotonAnt);
    const btnSig = document.getElementById(idBotonSig);

    if (carrusel && btnAnt && btnSig) {
      const cantidadScroll = 320; 
      btnSig.addEventListener('click', () => carrusel.scrollBy({ left: cantidadScroll, behavior: 'smooth' }));
      btnAnt.addEventListener('click', () => carrusel.scrollBy({ left: -cantidadScroll, behavior: 'smooth' }));
    }
  };

  // Ahora sí llamamos a la función porque ya existe
  activarCarrusel('carruselExplorar', 'btnAntExplorar', 'btnSigExplorar');
  activarCarrusel('carruselMasVendidos', 'btnAntMasVendidos', 'btnSigMasVendidos');
  activarCarrusel('carruselCombos', 'btnAntCombos', 'btnSigCombos');


  // CALCULADORA HARRIS-BENEDICT
  const formCalculadora = document.getElementById('formCalculadora');
  const resultadosCalc = document.getElementById('resultadosCalc');
  const tituloResultados = document.getElementById('tituloResultados');
  const cuerpoTablaCalc = document.getElementById('cuerpoTablaCalc');

  if (formCalculadora) {
    formCalculadora.addEventListener('submit', (e) => {
      e.preventDefault();
    
      const sexo = document.getElementById('calcSexo').value;
      const edad = parseInt(document.getElementById('calcEdad').value);
      const peso = parseFloat(document.getElementById('calcPeso').value);
      const altura = parseFloat(document.getElementById('calcAltura').value);
      const actividad = parseFloat(document.getElementById('calcActividad').value);
      const objetivo = document.getElementById('calcObjetivo').value;

      let tmb = 0;
      if (sexo === 'hombre') {
        tmb = 66.5 + (13.75 * peso) + (5.003 * altura) - (6.75 * edad);
      } else {
        tmb = 655.1 + (9.563 * peso) + (1.850 * altura) - (4.676 * edad);
      }

      const tdee = Math.round(tmb * actividad);
      let tableHTML = '';
      
      if (objetivo === 'deficit') {
        tituloResultados.textContent = 'Tus calorías para Déficit Calórico:';
        tableHTML = `
          <tr><td>Mantenimiento (Tu gasto actual)</td><td>${tdee}</td></tr>
          <tr style="background-color: #fef9c3;"><td>Déficit ligero (-250 kcal)</td><td>${tdee - 250}</td></tr>
          <tr style="background-color: #fef08a;"><td>Déficit moderado (-500 kcal)</td><td>${tdee - 500}</td></tr>
          <tr style="background-color: #fde047; font-weight: bold;"><td>Déficit agresivo (-750 kcal)</td><td>${tdee - 750}</td></tr>
        `;
      } 
      else if (objetivo === 'superavit') {
        tituloResultados.textContent = 'Tus calorías para Ganar Masa (Superávit):';
        tableHTML = `
          <tr><td>Mantenimiento (Tu gasto actual)</td><td>${tdee}</td></tr>
          <tr style="background-color: #fef9c3;"><td>Superávit ligero (+250 kcal)</td><td>${tdee + 250}</td></tr>
          <tr style="background-color: #fef08a;"><td>Superávit moderado (+500 kcal)</td><td>${tdee + 500}</td></tr>
          <tr style="background-color: #a3e635; font-weight: bold;"><td>Superávit agresivo (+750 kcal)</td><td>${tdee + 750}</td></tr>
        `;
      } 
      else {
        tituloResultados.textContent = 'Tus calorías para Mantener tu peso:';
        tableHTML = `
          <tr style="background-color: #111; color: #fff;"><td>Mantenimiento estricto</td><td>${tdee}</td></tr>
        `;
      }

      cuerpoTablaCalc.innerHTML = tableHTML;
      resultadosCalc.style.display = 'block';
      
      const botonSubmit = formCalculadora.querySelector('button[type="submit"]');
      botonSubmit.textContent = 'Volver a calcular';
      
      resultadosCalc.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }
});

// =========================================
  // CARRITO DE COMPRAS A WHATSAPP
  // =========================================
  
  // 1. Configuración
  const numeroWhatsApp = "5491100000000"; // ACÁ PONÉ TU NÚMERO REAL CON CÓDIGO DE PAÍS
  let carrito = [];

  // 2. Elementos del DOM
  const botonesAgregar = document.querySelectorAll('.js-agregar-carrito');
  const contadorCarrito = document.querySelector('.encabezado__contador-carrito');
  const botonFlotanteWsp = document.querySelector('.whatsapp-flotante');
  const iconoCarritoNav = document.querySelector('[aria-label="Carrito"]');

  // 3. Función para actualizar el numerito rojo arriba
  const actualizarContador = () => {
    if (contadorCarrito) {
      contadorCarrito.textContent = carrito.length;
      // Pequeña animación para que se note que se agregó algo
      contadorCarrito.style.transform = 'scale(1.5)';
      setTimeout(() => contadorCarrito.style.transform = 'scale(1)', 200);
    }
  };

  // 4. Lógica para agregar productos al carrito
  botonesAgregar.forEach(boton => {
    boton.addEventListener('click', (e) => {
      e.preventDefault();
      
      // Leer los datos que pusimos en el HTML
      const nombre = boton.getAttribute('data-nombre');
      const precio = parseInt(boton.getAttribute('data-precio'));

      // Guardar en la lista del carrito
      carrito.push({ nombre, precio });
      actualizarContador();

      // Feedback visual: Cambiar el botón temporalmente
      const textoOriginal = boton.textContent;
      boton.textContent = "¡AGREGADO ✔!";
      boton.style.backgroundColor = "var(--color-primario)";
      boton.style.color = "var(--color-oscuro)";
      
      setTimeout(() => {
        boton.textContent = textoOriginal;
        boton.style.backgroundColor = "";
        boton.style.color = "";
      }, 1500);
    });
  });

  // 5. Generar el mensaje y enviar a WhatsApp
  const enviarPedidoWsp = (e) => {
    e.preventDefault();

    // Si el carrito está vacío, manda un mensaje de consulta general
    if (carrito.length === 0) {
      window.open(`https://wa.me/${numeroWhatsApp}?text=Hola,%20tengo%20una%20consulta%20sobre%20los%20suplementos.`, '_blank');
      return;
    }

    // Si hay productos, armamos el ticket
    let mensaje = "Hola buenas, quiero estos productos:\n\n";
    let total = 0;

    carrito.forEach(item => {
      mensaje += `✅ ${item.nombre} ($${item.precio.toLocaleString('es-AR')})\n`;
      total += item.precio;
    });

    mensaje += `\n*Total estimado: $${total.toLocaleString('es-AR')}*\n\nPor favor, confirmame stock y medios de pago. ¡Gracias!`;

    // Codificar el texto para que los espacios y saltos de línea funcionen en la URL
    const urlWsp = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`;
    window.open(urlWsp, '_blank');
  };

  // 6. Asignar el envío al botón de WhatsApp y al ícono del carrito en el menú
  if (botonFlotanteWsp) botonFlotanteWsp.addEventListener('click', enviarPedidoWsp);
  if (iconoCarritoNav) iconoCarritoNav.addEventListener('click', enviarPedidoWsp);