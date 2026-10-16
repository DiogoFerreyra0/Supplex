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
  // 2. LÓGICA DEL CARRUSEL DEL HERO Y HORIZONTALES
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
  // 3. CALCULADORA HARRIS-BENEDICT
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

      let tmb = (sexo === 'hombre') 
        ? 66.5 + (13.75 * peso) + (5.003 * altura) - (6.75 * edad) 
        : 655.1 + (9.563 * peso) + (1.850 * altura) - (4.676 * edad);

      const tdee = Math.round(tmb * actividad);
      let tableHTML = '';
      
      if (objetivo === 'deficit') {
        tituloResultados.textContent = 'Tus calorías para Déficit Calórico:';
        tableHTML = `
          <tr><td>Mantenimiento</td><td>${tdee}</td></tr>
          <tr style="background-color: #fef9c3;"><td>Déficit ligero</td><td>${tdee - 250}</td></tr>
          <tr style="background-color: #fde047; font-weight: bold;"><td>Déficit agresivo</td><td>${tdee - 750}</td></tr>`;
      } else if (objetivo === 'superavit') {
        tituloResultados.textContent = 'Tus calorías para Ganar Masa:';
        tableHTML = `
          <tr><td>Mantenimiento</td><td>${tdee}</td></tr>
          <tr style="background-color: #fef9c3;"><td>Superávit ligero</td><td>${tdee + 250}</td></tr>
          <tr style="background-color: #a3e635; font-weight: bold;"><td>Superávit agresivo</td><td>${tdee + 750}</td></tr>`;
      } else {
        tituloResultados.textContent = 'Tus calorías para Mantener tu peso:';
        tableHTML = `<tr style="background-color: #111; color: #fff;"><td>Mantenimiento estricto</td><td>${tdee}</td></tr>`;
      }

      cuerpoTablaCalc.innerHTML = tableHTML;
      resultadosCalc.style.display = 'block';
      resultadosCalc.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  }

  // =========================================
  // 4. SISTEMA DE CARRITO (INTEGRACIÓN BD + WSP)
  // =========================================
  const numeroWhatsApp = "5491100000000"; 
  let carrito = [];
  let cuponAplicado = null;
  let descuentoPorcentaje = 0;
  const cuponesValidos = { "GYM": 15, "SUPPLEX10": 10 };

  const contadorCarrito = document.querySelector('.encabezado__contador-carrito');
  const carritoModal = document.getElementById('carritoModal');
  const carritoFondo = document.getElementById('carritoFondo');
  const btnCerrarCarrito = document.getElementById('btnCerrarCarrito');
  const listaCarrito = document.getElementById('listaCarrito');
  const cartSubtotal = document.getElementById('cartSubtotal');
  const lineaDescuento = document.getElementById('lineaDescuento');
  const cartNombreCupon = document.getElementById('cartNombreCupon');
  const cartMontoDescuento = document.getElementById('cartMontoDescuento');
  const totalCarrito = document.getElementById('totalCarrito');
  const btnEnviarWhatsApp = document.getElementById('btnEnviarWhatsApp');
  const inputCupon = document.getElementById('cartCupon');
  const btnAplicarCupon = document.getElementById('btnAplicarCupon');
  const msjCupon = document.getElementById('msjCupon');

  const abrirCarrito = (e) => {
    if(e) e.preventDefault();
    carritoModal?.classList.add('carrito-modal--activo');
    renderizarCarrito();
  };
  const cerrarCarrito = () => carritoModal?.classList.remove('carrito-modal--activo');

  document.querySelector('[aria-label="Carrito"]')?.addEventListener('click', abrirCarrito);
  btnCerrarCarrito?.addEventListener('click', cerrarCarrito);
  carritoFondo?.addEventListener('click', cerrarCarrito);

  btnAplicarCupon?.addEventListener('click', () => {
    const codigo = inputCupon.value.trim().toUpperCase();
    if (cuponesValidos[codigo]) {
      cuponAplicado = codigo;
      descuentoPorcentaje = cuponesValidos[codigo];
      msjCupon.textContent = `¡Cupón ${codigo} aplicado!`;
      msjCupon.style.color = "#25d366";
      msjCupon.style.display = "block";
    } else {
      cuponAplicado = null;
      descuentoPorcentaje = 0;
      msjCupon.textContent = "Cupón inválido.";
      msjCupon.style.color = "red";
      msjCupon.style.display = "block";
    }
    renderizarCarrito();
  });

  const renderizarCarrito = () => {
    let cantidadTotal = 0;
    let subtotal = 0;
    listaCarrito.innerHTML = '';

    if (carrito.length === 0) {
      listaCarrito.innerHTML = '<p class="carrito-modal__vacio">Tu carrito está vacío.</p>';
      if(contadorCarrito) contadorCarrito.textContent = "0";
      if(cartSubtotal) cartSubtotal.textContent = "$ 0";
      if(lineaDescuento) lineaDescuento.style.display = "none";
      if(totalCarrito) totalCarrito.textContent = "$ 0";
      return;
    }

    carrito.forEach((item, index) => {
      cantidadTotal += item.cantidad;
      let totalItem = item.precio * item.cantidad;
      subtotal += totalItem;

      const divItem = document.createElement('div');
      divItem.classList.add('carrito-item');
      divItem.innerHTML = `
        <div class="carrito-item__info" style="flex-direction: row; align-items: center;">
          <span class="carrito-item__cant">${item.cantidad}x</span>
          <div style="display: flex; flex-direction: column;">
            <span class="carrito-item__nombre">${item.nombre}</span>
            <span class="carrito-item__precio">$${totalItem.toLocaleString('es-AR')}</span>
          </div>
        </div>
        <button class="carrito-item__eliminar" onclick="eliminarDelCarrito(${index})">×</button>
      `;
      listaCarrito.appendChild(divItem);
    });

    if(contadorCarrito) contadorCarrito.textContent = cantidadTotal;

    let descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    let totalF = subtotal - descuentoMonto;

    if(cartSubtotal) cartSubtotal.textContent = `$ ${subtotal.toLocaleString('es-AR')}`;
    if(totalCarrito) totalCarrito.textContent = `$ ${totalF.toLocaleString('es-AR')}`;

    if (descuentoMonto > 0 && lineaDescuento) {
      lineaDescuento.style.display = "flex";
      cartNombreCupon.textContent = cuponAplicado;
      cartMontoDescuento.textContent = `-$ ${descuentoMonto.toLocaleString('es-AR')}`;
    } else if (lineaDescuento) {
      lineaDescuento.style.display = "none";
    }
  };

  window.eliminarDelCarrito = (index) => {
    carrito.splice(index, 1);
    renderizarCarrito();
    animarContador();
  };

  const animarContador = () => {
    if (contadorCarrito) {
      contadorCarrito.style.transform = 'scale(1.5)';
      setTimeout(() => contadorCarrito.style.transform = 'scale(1)', 200);
    }
  };

  // FINALIZAR COMPRA: ENVÍO AL SERVIDOR Y WSP
  btnEnviarWhatsApp?.addEventListener('click', (e) => {
    e.preventDefault();
    if (carrito.length === 0) return;

    const nombreCliente = document.getElementById('cartNombre').value.trim();
    const direccionCliente = document.getElementById('cartDireccion').value.trim();

    if (!nombreCliente || !direccionCliente) {
      alert("⚠️ Por favor, completá tu Nombre y Dirección de Envío para procesar el pedido.");
      return;
    }

    const orderId = "PED-" + Math.floor(Math.random() * 90000 + 10000); 
    const fecha = new Date().toLocaleDateString();
    
    let subtotal = 0;
    carrito.forEach(item => subtotal += (item.precio * item.cantidad));
    let descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    let totalFinal = subtotal - descuentoMonto;

    let msjWsp = `*NUEVO PEDIDO: ${orderId}*\n📅 Fecha: ${fecha}\n👤 Cliente: ${nombreCliente}\n📍 Dirección: ${direccionCliente}\n---------------------------\n*ARTÍCULOS:*\n`;
    carrito.forEach(item => {
      msjWsp += `🔸 ${item.cantidad}x ${item.nombre} ($${(item.precio * item.cantidad).toLocaleString('es-AR')})\n`;
    });
    msjWsp += `---------------------------\nSubtotal: $${subtotal.toLocaleString('es-AR')}\n`;
    if (descuentoMonto > 0) msjWsp += `Descuento (${cuponAplicado}): -$${descuentoMonto.toLocaleString('es-AR')}\n`;
    msjWsp += `*TOTAL A PAGAR: $${totalFinal.toLocaleString('es-AR')}*\n\nPor favor, confirmame los métodos de pago. ¡Gracias!`;

    btnEnviarWhatsApp.textContent = "PROCESANDO...";

    const datosPedido = {
      orderId: orderId,
      nombre: nombreCliente,
      direccion: direccionCliente,
      cupon: cuponAplicado,
      subtotal: subtotal,
      descuento: descuentoMonto,
      total: totalFinal,
      articulos: carrito
    };

    // Intentamos guardar el pedido en tu Base de Datos PHP
    // Detectamos si el cliente está comprando desde /pages/ o desde el inicio
    const rutaPedido = window.location.pathname.includes('/pages/') ? '../guardar_pedido.php' : 'guardar_pedido.php';

    // Intentamos guardar el pedido en tu Base de Datos PHP usando la ruta inteligente
    fetch(rutaPedido, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datosPedido)
    })
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datosPedido)
    })
    .then(respuesta => respuesta.json())
    .then(datosDelServidor => {
        // Todo salió bien en el servidor
        window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(msjWsp)}`, '_blank');
        reiniciarFormularioCarrito();
    })
    .catch(error => {
        // FALLBACK: Si todavía no tenés XAMPP/PHP configurado, igual abre WhatsApp para que no se trabe
        console.warn("Base de datos no disponible aún. Abriendo WhatsApp directamente.");
        window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(msjWsp)}`, '_blank');
        reiniciarFormularioCarrito();
    });

    function reiniciarFormularioCarrito() {
        carrito = [];
        cuponAplicado = null;
        descuentoPorcentaje = 0;
        document.getElementById('cartNombre').value = "";
        document.getElementById('cartDireccion').value = "";
        if(inputCupon) inputCupon.value = "";
        if(msjCupon) msjCupon.style.display = "none";
        btnEnviarWhatsApp.textContent = "FINALIZAR COMPRA";
        cerrarCarrito();
        renderizarCarrito();
        animarContador();
    }
  });

  // =========================================
  // 5. EVENTOS GLOBALES (AGREGAR AL CARRITO / VISTA RÁPIDA)
  // =========================================
  const productoModal = document.getElementById('productoModal');
  const modalProdImg = document.getElementById('modalProdImg');
  const modalProdCat = document.getElementById('modalProdCat');
  const modalProdTitulo = document.getElementById('modalProdTitulo');
  const modalProdDesc = document.getElementById('modalProdDesc');
  const modalProdPrecio = document.getElementById('modalProdPrecio');
  const modalProdBtn = document.getElementById('modalProdBtn');

  const cerrarVistaRapida = () => productoModal?.classList.remove('producto-modal--activo');
  document.getElementById('btnCerrarProducto')?.addEventListener('click', cerrarVistaRapida);
  document.getElementById('btnCerrarFondoProducto')?.addEventListener('click', cerrarVistaRapida);

  document.addEventListener('click', (e) => {
    const botonAgregar = e.target.closest('.js-agregar-carrito');
    if (botonAgregar) {
      e.preventDefault();
      const nombre = botonAgregar.getAttribute('data-nombre');
      const precio = parseInt(botonAgregar.getAttribute('data-precio'));

      const productoExistente = carrito.find(item => item.nombre === nombre);
      if (productoExistente) {
        productoExistente.cantidad++;
      } else {
        carrito.push({ nombre, precio, cantidad: 1 });
      }
      
      renderizarCarrito();
      animarContador();

      const textoOriginal = botonAgregar.textContent;
      botonAgregar.textContent = "¡AGREGADO ✔!";
      botonAgregar.style.backgroundColor = "var(--color-primario)";
      botonAgregar.style.color = "var(--color-oscuro)";
      setTimeout(() => {
        botonAgregar.textContent = textoOriginal;
        botonAgregar.style.backgroundColor = "";
        botonAgregar.style.color = "";
      }, 1500);
      return; 
    }

    const clicEnTarjeta = e.target.closest('.producto-tarjeta, .combo-tarjeta');
    const clicEnFavorito = e.target.closest('.producto-tarjeta__favorito');

    if (clicEnTarjeta && !clicEnFavorito) {
      e.preventDefault();
      const elementoNombre = clicEnTarjeta.querySelector('.producto-tarjeta__nombre, .combo-tarjeta__nombre');
      
      if (elementoNombre && typeof window.inventarioProductos !== 'undefined') {
        const nombreBusqueda = elementoNombre.textContent.trim();
        const productoData = window.inventarioProductos.find(p => p.nombre.includes(nombreBusqueda));

        if (productoData && productoModal) {
          modalProdImg.textContent = productoData.img;
          modalProdCat.textContent = productoData.cat;
          modalProdTitulo.textContent = productoData.nombre;
          modalProdDesc.textContent = productoData.desc || "Suplemento deportivo de alta calidad.";
          modalProdPrecio.textContent = `$ ${productoData.precio.toLocaleString('es-AR')}`;
          modalProdBtn.setAttribute('data-nombre', productoData.nombre);
          modalProdBtn.setAttribute('data-precio', productoData.precio);
          productoModal.classList.add('producto-modal--activo');
        }
      }
    }
  });

  // =========================================
  // 6. BUSCADOR EN TIEMPO REAL
  // =========================================
  const buscadorModal = document.getElementById('buscadorModal');
  const inputBuscador = document.getElementById('inputBuscador');
  const btnCerrarBuscador = document.getElementById('btnCerrarBuscador');
  const resultadosBuscador = document.getElementById('resultadosBuscador');
  const btnAbrirBuscador = document.querySelector('[aria-label="Buscar"]');

  if (btnAbrirBuscador && buscadorModal) {
    btnAbrirBuscador.addEventListener('click', (e) => {
      e.preventDefault();
      buscadorModal.classList.add('buscador-modal--activo');
      setTimeout(() => inputBuscador?.focus(), 100); 
    });
  }

  if (btnCerrarBuscador && buscadorModal) {
    btnCerrarBuscador.addEventListener('click', () => {
      buscadorModal.classList.remove('buscador-modal--activo');
      if (inputBuscador) inputBuscador.value = '';
      if (resultadosBuscador) resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio">Escribí arriba para buscar productos.</p>';
    });
  }

  let temporizadorBuscador;
  if (inputBuscador && resultadosBuscador) {
    inputBuscador.addEventListener('keyup', (e) => {
      clearTimeout(temporizadorBuscador);
      temporizadorBuscador = setTimeout(() => {
        const textoBusqueda = e.target.value.toLowerCase().trim();
        if (textoBusqueda === '') {
          resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio">Escribí arriba para buscar productos.</p>';
          return;
        }

        if(typeof window.inventarioProductos !== 'undefined') {
            let resultados = window.inventarioProductos.filter(producto => 
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
                    <div class="producto-tarjeta__imagen-caja"><div class="producto-tarjeta__placeholder">${prod.img}</div></div>
                    <div class="producto-tarjeta__info">
                      <span class="producto-tarjeta__marca">${prod.cat}</span>
                      <h3 class="producto-tarjeta__nombre" style="font-size: 0.9rem;">${prod.nombre}</h3>
                      <div class="producto-tarjeta__precios"><span class="producto-tarjeta__precio-actual">$ ${prod.precio.toLocaleString('es-AR')}</span></div>
                      <button class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${prod.nombre}" data-precio="${prod.precio}">AL CARRITO</button>
                    </div>
                  </article>
                `;
                resultadosBuscador.insertAdjacentHTML('beforeend', tarjetaHTML);
              });
            }
        }
      }, 300);
    });
  }

  // =========================================
  // 7. FORMULARIO CONTACTO Y FAQ
  // =========================================
  const formContacto = document.getElementById('formContacto');
  const msjExitoContacto = document.getElementById('msjExitoContacto');
  const btnEnviarContacto = document.getElementById('btnEnviarContacto');

  if (formContacto) {
    formContacto.addEventListener('submit', (e) => {
      e.preventDefault(); 
      const textoOriginalBtn = btnEnviarContacto.textContent;
      btnEnviarContacto.textContent = "ENVIANDO...";
      btnEnviarContacto.style.opacity = "0.7";
      setTimeout(() => {
        formContacto.reset();
        btnEnviarContacto.textContent = textoOriginalBtn;
        btnEnviarContacto.style.opacity = "1";
        msjExitoContacto.classList.add('contacto__exito--activo');
        setTimeout(() => msjExitoContacto.classList.remove('contacto__exito--activo'), 4000);
      }, 1500);
    });
  }

  const faqModal = document.getElementById('faqModal');
  const btnCerrarFaq = document.getElementById('btnCerrarFaq');
  const btnCerrarFondoFaq = document.getElementById('btnCerrarFondoFaq');
  document.querySelectorAll('.js-abrir-faq').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      faqModal?.classList.add('faq-modal--activo');
    });
  });
  const cerrarFaq = () => faqModal?.classList.remove('faq-modal--activo');
  btnCerrarFaq?.addEventListener('click', cerrarFaq);
  btnCerrarFondoFaq?.addEventListener('click', cerrarFaq);

  document.querySelectorAll('.faq__pregunta').forEach(pregunta => {
    pregunta.addEventListener('click', () => {
      const itemActual = pregunta.parentElement;
      document.querySelectorAll('.faq__item').forEach(item => {
        if (item !== itemActual) item.classList.remove('faq__item--activo');
      });
      itemActual.classList.toggle('faq__item--activo');
    });
  });

  // =========================================
  // 8. OBTENER PRODUCTOS DEL BACKEND PHP
  // =========================================
  window.inventarioProductos = []; 
  
  // Función para renderizar el catálogo si estamos en la página productos.html
  const renderizarCatalogo = (filtro) => {
    const grillaCatalogo = document.getElementById('grillaCatalogo');
    if (!grillaCatalogo || window.inventarioProductos.length === 0) return;
    
    grillaCatalogo.innerHTML = '';
    let productosFiltrados = window.inventarioProductos;
    
    if (filtro !== 'todos') {
      productosFiltrados = window.inventarioProductos.filter(prod => prod.cat.toLowerCase() === filtro.toLowerCase());
    }

    if (productosFiltrados.length === 0) {
      grillaCatalogo.innerHTML = '<div class="catalogo__vacio">Aún no hay productos en esta categoría.</div>';
      return;
    }

    productosFiltrados.forEach(prod => {
      const tarjeta = `
        <article class="producto-tarjeta tarjeta-sombra_efecto" style="border: 1px solid var(--color-borde);">
          <div class="producto-tarjeta__imagen-caja"><div class="producto-tarjeta__placeholder">${prod.img}</div></div>
          <div class="producto-tarjeta__info">
            <span class="producto-tarjeta__marca">${prod.cat}</span>
            <h3 class="producto-tarjeta__nombre" style="font-size: 0.95rem;">${prod.nombre}</h3>
            <div class="producto-tarjeta__precios"><span class="producto-tarjeta__precio-actual">$ ${prod.precio.toLocaleString('es-AR')}</span></div>
            <button class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${prod.nombre}" data-precio="${prod.precio}">AL CARRITO</button>
          </div>
        </article>
      `;
      grillaCatalogo.insertAdjacentHTML('beforeend', tarjeta);
    });
  };

  // Llamada al backend
  // Detecta si estamos adentro de la carpeta /pages/ o en el inicio
  const rutaPHP = window.location.pathname.includes('/pages/') ? '../obtener_productos.php' : 'obtener_productos.php';

  fetch(rutaPHP)
    .then(respuesta => respuesta.json())
    .then(datosDelServidor => {
        window.inventarioProductos = datosDelServidor;
        
        // Manejo de filtros en la página del catálogo
        const botonesFiltro = document.querySelectorAll('.catalogo__filtro-btn');
        const urlParams = new URLSearchParams(window.location.search);
        const categoriaUrl = urlParams.get('cat');

        if (categoriaUrl) {
          renderizarCatalogo(categoriaUrl);
          botonesFiltro.forEach(b => {
            b.classList.remove('catalogo__filtro-btn--activo');
            if (b.getAttribute('data-filtro')?.toLowerCase() === categoriaUrl.toLowerCase()) {
              b.classList.add('catalogo__filtro-btn--activo');
            }
          });
        } else {
          renderizarCatalogo('todos');
        }

        botonesFiltro.forEach(boton => {
          boton.addEventListener('click', (e) => {
            botonesFiltro.forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
            e.target.classList.add('catalogo__filtro-btn--activo');
            renderizarCatalogo(e.target.getAttribute('data-filtro'));
          });
        });
    })
    .catch(error => {
        console.warn("PHP no conectado aún. El buscador y el catálogo estarán vacíos hasta configurar la base de datos.");
    });

});