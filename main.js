document.addEventListener('DOMContentLoaded', () => {

  // =========================================
  // 1. ENCABEZADO FIJO (Sticky Header)
  // =========================================
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

  // =========================================
  // 2. LÓGICA DEL CARRUSEL DEL HERO
  // =========================================
  const slidesHero = document.querySelectorAll('.portada__diapositiva');
  const puntosHero = document.querySelectorAll('.portada__punto');
  const btnAntHero = document.getElementById('btnAntHero');
  const btnSigHero = document.getElementById('btnSigHero');
  let slideActual = 0;

  if (slidesHero.length > 0 && btnAntHero && btnSigHero) {
    const mostrarSlide = (index) => {
      slidesHero.forEach(slide => slide.classList.remove('portada__diapositiva--activa'));
      puntosHero.forEach(punto => punto.classList.remove('portada__punto--activo'));

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

  // =========================================
  // 3. CARRUSELES HORIZONTALES (Productos/Combos)
  // =========================================
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

  activarCarrusel('carruselExplorar', 'btnAntExplorar', 'btnSigExplorar');
  activarCarrusel('carruselMasVendidos', 'btnAntMasVendidos', 'btnSigMasVendidos');
  activarCarrusel('carruselCombos', 'btnAntCombos', 'btnSigCombos');

  // =========================================
  // 4. CALCULADORA HARRIS-BENEDICT
  // =========================================
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

  // =========================================
  // 5. MODAL DEL CARRITO Y WHATSAPP
  // =========================================
  const numeroWhatsApp = "5491100000000"; // TU NÚMERO DE WHATSAPP ACÁ
  let carrito = [];

  const contadorCarrito = document.querySelector('.encabezado__contador-carrito');
  const iconoCarritoNav = document.querySelector('[aria-label="Carrito"]');
  const botonFlotanteWsp = document.querySelector('.whatsapp-flotante');
  const carritoModal = document.getElementById('carritoModal');
  const carritoFondo = document.getElementById('carritoFondo');
  const btnCerrarCarrito = document.getElementById('btnCerrarCarrito');
  const listaCarrito = document.getElementById('listaCarrito');
  const totalCarrito = document.getElementById('totalCarrito');
  const btnEnviarWhatsApp = document.getElementById('btnEnviarWhatsApp');

  const abrirCarrito = (e) => {
    if(e) e.preventDefault();
    carritoModal.classList.add('carrito-modal--activo');
    renderizarCarrito();
  };

  const cerrarCarrito = () => carritoModal.classList.remove('carrito-modal--activo');

  if(iconoCarritoNav) iconoCarritoNav.addEventListener('click', abrirCarrito);
  if(btnCerrarCarrito) btnCerrarCarrito.addEventListener('click', cerrarCarrito);
  if(carritoFondo) carritoFondo.addEventListener('click', cerrarCarrito);

  const renderizarCarrito = () => {
    if (contadorCarrito) contadorCarrito.textContent = carrito.length;
    
    listaCarrito.innerHTML = '';
    let total = 0;

    if (carrito.length === 0) {
      listaCarrito.innerHTML = '<p class="carrito-modal__vacio">Tu carrito está vacío. ¡Agregá algunos suplementos!</p>';
      totalCarrito.textContent = '$ 0';
      return;
    }

    carrito.forEach((item, index) => {
      total += item.precio;
      const divItem = document.createElement('div');
      divItem.classList.add('carrito-item');
      divItem.innerHTML = `
        <div class="carrito-item__info">
          <span class="carrito-item__nombre">${item.nombre}</span>
          <span class="carrito-item__precio">$${item.precio.toLocaleString('es-AR')}</span>
        </div>
        <button class="carrito-item__eliminar" onclick="eliminarDelCarrito(${index})" aria-label="Eliminar producto">×</button>
      `;
      listaCarrito.appendChild(divItem);
    });

    totalCarrito.textContent = `$ ${total.toLocaleString('es-AR')}`;
  };

  window.eliminarDelCarrito = (index) => {
    carrito.splice(index, 1);
    renderizarCarrito();
    if (contadorCarrito) {
      contadorCarrito.style.transform = 'scale(1.5)';
      setTimeout(() => contadorCarrito.style.transform = 'scale(1)', 200);
    }
  };

  // =========================================
  // DELEGACIÓN DE EVENTOS (CARRITO Y VISTA RÁPIDA)
  // =========================================
  
  // Elementos del Modal de Producto
  const productoModal = document.getElementById('productoModal');
  const modalProdImg = document.getElementById('modalProdImg');
  const modalProdCat = document.getElementById('modalProdCat');
  const modalProdTitulo = document.getElementById('modalProdTitulo');
  const modalProdDesc = document.getElementById('modalProdDesc');
  const modalProdPrecio = document.getElementById('modalProdPrecio');
  const modalProdBtn = document.getElementById('modalProdBtn');
  
  const cerrarVistaRapida = () => productoModal.classList.remove('producto-modal--activo');
  
  document.getElementById('btnCerrarProducto')?.addEventListener('click', cerrarVistaRapida);
  document.getElementById('btnCerrarFondoProducto')?.addEventListener('click', cerrarVistaRapida);

  document.addEventListener('click', (e) => {
    
    // 1. SI HACE CLIC EN "AGREGAR AL CARRITO"
    const botonAgregar = e.target.closest('.js-agregar-carrito');
    if (botonAgregar) {
      e.preventDefault();
      const nombre = botonAgregar.getAttribute('data-nombre');
      const precio = parseInt(botonAgregar.getAttribute('data-precio'));

      carrito.push({ nombre, precio });
      renderizarCarrito();
      
      if (contadorCarrito) {
        contadorCarrito.style.transform = 'scale(1.5)';
        setTimeout(() => contadorCarrito.style.transform = 'scale(1)', 200);
      }

      const textoOriginal = botonAgregar.textContent;
      botonAgregar.textContent = "¡AGREGADO ✔!";
      botonAgregar.style.backgroundColor = "var(--color-primario)";
      botonAgregar.style.color = "var(--color-oscuro)";
      
      setTimeout(() => {
        botonAgregar.textContent = textoOriginal;
        botonAgregar.style.backgroundColor = "";
        botonAgregar.style.color = "";
      }, 1500);
      return; // Cortamos acá para que no siga evaluando
    }

    // 2. SI HACE CLIC EN LA FOTO O EL TÍTULO (VISTA RÁPIDA)
    const clicEnTarjeta = e.target.closest('.producto-tarjeta, .combo-tarjeta');
    const clicEnFavorito = e.target.closest('.producto-tarjeta__favorito');

    // Si tocó una tarjeta, PERO NO fue el botón de carrito ni el corazón de favoritos
    if (clicEnTarjeta && !clicEnFavorito) {
      e.preventDefault();
      
      // Buscamos el nombre del producto en el HTML que se clickeó
      const elementoNombre = clicEnTarjeta.querySelector('.producto-tarjeta__nombre, .combo-tarjeta__nombre');
      
      if (elementoNombre && typeof inventarioProductos !== 'undefined') {
        const nombreBusqueda = elementoNombre.textContent.trim();
        
        // Lo buscamos en la base de datos de productos.js
        const productoData = inventarioProductos.find(p => p.nombre.includes(nombreBusqueda));

        if (productoData) {
          // Llenamos la ventana flotante con los datos
          modalProdImg.textContent = productoData.img;
          modalProdCat.textContent = productoData.cat;
          modalProdTitulo.textContent = productoData.nombre;
          modalProdDesc.textContent = productoData.desc || "Suplemento deportivo de alta calidad diseñado para mejorar tu rendimiento.";
          modalProdPrecio.textContent = `$ ${productoData.precio.toLocaleString('es-AR')}`;
          
          // Configuramos el botón de compra del modal con los datos exactos
          modalProdBtn.setAttribute('data-nombre', productoData.nombre);
          modalProdBtn.setAttribute('data-precio', productoData.precio);

          // Mostramos la ventana
          productoModal.classList.add('producto-modal--activo');
        }
      }
    }
  });

  // Enviar a WhatsApp
  if (btnEnviarWhatsApp) {
    btnEnviarWhatsApp.addEventListener('click', (e) => {
      e.preventDefault();
      if (carrito.length === 0) return;

      let mensaje = "Hola buenas, quiero confirmar la compra de estos productos:\n\n";
      let total = 0;

      carrito.forEach(item => {
        mensaje += `✅ ${item.nombre} ($${item.precio.toLocaleString('es-AR')})\n`;
        total += item.precio;
      });

      mensaje += `\n*Total a pagar: $${total.toLocaleString('es-AR')}*\n\nPor favor, pasame los datos para el pago. ¡Gracias!`;

      const urlWsp = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(mensaje)}`;
      window.open(urlWsp, '_blank');
      cerrarCarrito();
    });
  }

  if (botonFlotanteWsp) {
    botonFlotanteWsp.addEventListener('click', (e) => {
      e.preventDefault();
      window.open(`https://wa.me/${numeroWhatsApp}?text=Hola,%20tengo%20una%20consulta%20sobre%20sus%20productos.`, '_blank');
    });
  }

  // =========================================
  // 6. BUSCADOR EN TIEMPO REAL (MODAL)
  // =========================================
  const buscadorModal = document.getElementById('buscadorModal');
  const inputBuscador = document.getElementById('inputBuscador');
  const btnCerrarBuscador = document.getElementById('btnCerrarBuscador');
  const resultadosBuscador = document.getElementById('resultadosBuscador');
  const iconoLupaNav = document.querySelector('[aria-label="Buscar"]');

  if (iconoLupaNav) {
    iconoLupaNav.addEventListener('click', (e) => {
      e.preventDefault();
      buscadorModal.classList.add('buscador-modal--activo');
      // Pequeño delay para enfocar el input una vez terminada la animación del modal
      setTimeout(() => inputBuscador.focus(), 100); 
    });
  }

  if (btnCerrarBuscador) {
    btnCerrarBuscador.addEventListener('click', () => {
      buscadorModal.classList.remove('buscador-modal--activo');
      inputBuscador.value = '';
      resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio">Escribí arriba para buscar productos.</p>';
    });
  }

  let temporizadorBuscador;
  if (inputBuscador) {
    inputBuscador.addEventListener('keyup', (e) => {
      clearTimeout(temporizadorBuscador);
      
      temporizadorBuscador = setTimeout(() => {
        const textoBusqueda = e.target.value.toLowerCase().trim();

        if (textoBusqueda === '') {
          resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio">Escribí arriba para buscar productos.</p>';
          return;
        }

        // Verificamos que inventarioProductos exista (viene del archivo productos.js)
        if(typeof inventarioProductos !== 'undefined') {
            let resultados = inventarioProductos.filter(producto => 
              producto.nombre.toLowerCase().includes(textoBusqueda) || 
              producto.cat.toLowerCase().includes(textoBusqueda)
            );

            const resultadosLimitados = resultados.slice(0, 12);
            resultadosBuscador.innerHTML = '';

            if (resultadosLimitados.length === 0) {
              resultadosBuscador.innerHTML = `<p class="buscador-modal__vacio">No encontramos nada para "<strong>${textoBusqueda}</strong>".</p>`;
            } else {
              resultadosLimitados.forEach(prod => {
                const tarjetaHTML = `
                  <article class="producto-tarjeta tarjeta-sombra_efecto" style="border: 1px solid var(--color-borde);">
                    <div class="producto-tarjeta__imagen-caja">
                      <div class="producto-tarjeta__placeholder">${prod.img}</div>
                    </div>
                    <div class="producto-tarjeta__info">
                      <span class="producto-tarjeta__marca">${prod.cat}</span>
                      <h3 class="producto-tarjeta__nombre" style="font-size: 0.9rem;">${prod.nombre}</h3>
                      <div class="producto-tarjeta__precios">
                        <span class="producto-tarjeta__precio-actual">$ ${prod.precio.toLocaleString('es-AR')}</span>
                      </div>
                      <button class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${prod.nombre}" data-precio="${prod.precio}">AL CARRITO</button>
                    </div>
                  </article>
                `;
                resultadosBuscador.insertAdjacentHTML('beforeend', tarjetaHTML);
              });

              if (resultados.length > 12) {
                 resultadosBuscador.insertAdjacentHTML('beforeend', `
                   <p class="buscador-modal__vacio" style="width: 100%; grid-column: 1 / -1;">
                     Mostrando los 12 mejores resultados. Sé más específico en tu búsqueda.
                   </p>
                 `);
              }
            }
        } else {
             resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio" style="color:red;">Error: No se pudo cargar el archivo de productos.</p>';
        }
      }, 300);
    });
  }

});