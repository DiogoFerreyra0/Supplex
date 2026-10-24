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
      if (puntosHero[index]) puntosHero[index].classList.add('portada__punto--activo');
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
    if (e) e.preventDefault();
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
    if (!listaCarrito) return;
    let cantidadTotal = 0;
    let subtotal = 0;
    listaCarrito.innerHTML = '';

    if (carrito.length === 0) {
      listaCarrito.innerHTML = '<p class="carrito-modal__vacio">Tu carrito está vacío.</p>';
      if (contadorCarrito) contadorCarrito.textContent = "0";
      if (cartSubtotal) cartSubtotal.textContent = "$ 0";
      if (lineaDescuento) lineaDescuento.style.display = "none";
      if (totalCarrito) totalCarrito.textContent = "$ 0";
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

    if (contadorCarrito) contadorCarrito.textContent = cantidadTotal;

    let descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    let totalF = subtotal - descuentoMonto;

    if (cartSubtotal) cartSubtotal.textContent = `$ ${subtotal.toLocaleString('es-AR')}`;
    if (totalCarrito) totalCarrito.textContent = `$ ${totalF.toLocaleString('es-AR')}`;

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

    const rutaPedido = window.location.pathname.includes('/pages/') ? '../guardar_pedido.php' : 'guardar_pedido.php';

    fetch(rutaPedido, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(datosPedido)
    })
    .then(respuesta => respuesta.json())
    .then(() => {
        window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(msjWsp)}`, '_blank');
        reiniciarFormularioCarrito();
    })
    .catch(() => {
        console.warn("Base de datos de pedidos no disponible aún. Abriendo WhatsApp directamente.");
        window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(msjWsp)}`, '_blank');
        reiniciarFormularioCarrito();
    });

    function reiniciarFormularioCarrito() {
        carrito = [];
        cuponAplicado = null;
        descuentoPorcentaje = 0;
        document.getElementById('cartNombre').value = "";
        document.getElementById('cartDireccion').value = "";
        if (inputCupon) inputCupon.value = "";
        if (msjCupon) msjCupon.style.display = "none";
        btnEnviarWhatsApp.textContent = "FINALIZAR COMPRA";
        cerrarCarrito();
        renderizarCarrito();
        animarContador();
    }
  });

  // =========================================
  // 5. EVENTOS GLOBALES (BOTÓN AL CARRITO vs CARD FLOTANTE DE INFO)
  // =========================================
  const productoModal = document.getElementById('productoModal');
  const modalProdImg = document.getElementById('modalProdImg');
  const modalProdCat = document.getElementById('modalProdCat');
  const modalProdTitulo = document.getElementById('modalProdTitulo');
  const modalProdDesc = document.getElementById('modalProdDesc');
  const modalProdStock = document.getElementById('modalProdStock');
  const modalProdPrecio = document.getElementById('modalProdPrecio');
  const modalProdBtn = document.getElementById('modalProdBtn');

  const cerrarVistaRapida = () => productoModal?.classList.remove('producto-modal--activo');
  document.getElementById('btnCerrarProducto')?.addEventListener('click', cerrarVistaRapida);
  document.getElementById('btnCerrarFondoProducto')?.addEventListener('click', cerrarVistaRapida);

  document.addEventListener('click', (e) => {
    // A) Si presionó el botón "AL CARRITO" (en la tarjeta o dentro del modal)
    const botonAgregar = e.target.closest('.js-agregar-carrito');
    if (botonAgregar) {
      e.preventDefault();
      e.stopPropagation();

      const nombre = botonAgregar.getAttribute('data-nombre') || 'Suplemento Supplex';
      const precio = parseFloat(botonAgregar.getAttribute('data-precio')) || 0;

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
      }, 1200);
      return; 
    }

    // B) Si presionó cualquier otra parte de la tarjeta del producto, abre la Card Flotante
    const clicEnTarjeta = e.target.closest('.producto-tarjeta, .combo-tarjeta');
    const clicEnFavorito = e.target.closest('.producto-tarjeta__favorito');

    if (clicEnTarjeta && !clicEnFavorito && productoModal) {
      e.preventDefault();

      // 1. Buscamos primero por data-id en el inventario traído de la BD
      const idTarjeta = clicEnTarjeta.getAttribute('data-id');
      const elementoNombre = clicEnTarjeta.querySelector('.producto-tarjeta__nombre, .combo-tarjeta__nombre');
      const nombreBusqueda = elementoNombre ? elementoNombre.textContent.trim() : '';

      let productoData = null;

      if (Array.isArray(window.inventarioProductos) && window.inventarioProductos.length > 0) {
        if (idTarjeta !== null && idTarjeta !== '') {
          productoData = window.inventarioProductos.find(p => String(p.id) === String(idTarjeta));
        }
        if (!productoData && nombreBusqueda) {
          productoData = window.inventarioProductos.find(p => 
            p.nombre.trim().toLowerCase() === nombreBusqueda.toLowerCase() ||
            p.nombre.toLowerCase().includes(nombreBusqueda.toLowerCase())
          );
        }
      }

      // 2. Si no está en el array (o es una card fija del HTML), leemos los atributos data-* de la tarjeta
      if (!productoData) {
        const btnTarjeta = clicEnTarjeta.querySelector('.js-agregar-carrito');
        productoData = {
          nombre: clicEnTarjeta.getAttribute('data-nombre') || nombreBusqueda || 'Suplemento Deportivo',
          cat: clicEnTarjeta.getAttribute('data-cat') || clicEnTarjeta.querySelector('.producto-tarjeta__marca, .combo-tarjeta__etiqueta')?.textContent || 'General',
          desc: clicEnTarjeta.getAttribute('data-desc') || clicEnTarjeta.querySelector('.combo-tarjeta__desc, .producto-tarjeta__variante')?.textContent || 'Suplemento deportivo de alta calidad para potenciar tu entrenamiento.',
          precio: parseFloat(clicEnTarjeta.getAttribute('data-precio') || btnTarjeta?.getAttribute('data-precio')) || 0,
          img: clicEnTarjeta.getAttribute('data-img') || 'SUPPLEX',
          stock: parseInt(clicEnTarjeta.getAttribute('data-stock')) || 10
        };
      }

      // 3. Rellenamos la Card Flotante (#productoModal) con todos los datos del producto
      if (modalProdImg) {
        const tieneFoto = productoData.img && productoData.img.startsWith('http');
        modalProdImg.innerHTML = tieneFoto 
          ? `<img src="${productoData.img}" alt="${productoData.nombre}" style="width: 100%; height: 100%; object-fit: contain;">`
          : `<div class="producto-tarjeta__placeholder">${productoData.img || 'SUPPLEX'}</div>`;
      }
      if (modalProdCat) modalProdCat.textContent = productoData.cat || 'General';
      if (modalProdTitulo) modalProdTitulo.textContent = productoData.nombre;
      if (modalProdDesc) {
        modalProdDesc.textContent = (productoData.desc && productoData.desc.trim() !== '') 
          ? productoData.desc 
          : "Suplemento deportivo de alta calidad seleccionado para maximizar tu rendimiento y recuperación.";
      }
      if (modalProdStock) {
        const stockCant = productoData.stock ?? 10;
        modalProdStock.textContent = stockCant > 0 
          ? `✔ Stock disponible (${stockCant} unidades)` 
          : `✖ Sin stock disponible en este momento`;
        modalProdStock.style.color = stockCant > 0 ? '#22c55e' : '#ef4444';
      }
      if (modalProdPrecio) {
        modalProdPrecio.textContent = `$ ${Number(productoData.precio || 0).toLocaleString('es-AR')}`;
      }
      if (modalProdBtn) {
        modalProdBtn.setAttribute('data-nombre', productoData.nombre);
        modalProdBtn.setAttribute('data-precio', productoData.precio || 0);
      }

      // 4. Mostramos el modal flotante
      productoModal.classList.add('producto-modal--activo');
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

        if (Array.isArray(window.inventarioProductos)) {
          let resultados = window.inventarioProductos.filter(producto => 
            producto.nombre.toLowerCase().includes(textoBusqueda) || 
            (producto.cat && producto.cat.toLowerCase().includes(textoBusqueda)) ||
            (producto.desc && producto.desc.toLowerCase().includes(textoBusqueda))
          );

          const resultadosLimitados = resultados.slice(0, 12);
          resultadosBuscador.innerHTML = '';

          if (resultadosLimitados.length === 0) {
            resultadosBuscador.innerHTML = `<p class="buscador-modal__vacio">No encontramos nada para "<strong>${textoBusqueda}</strong>".</p>`;
          } else {
            resultadosLimitados.forEach(prod => {
              const tarjetaHTML = crearHTMLTarjeta(prod);
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
  
  const escaparAtributo = (texto) => String(texto ?? '').replace(/"/g, '&quot;');

  const crearHTMLTarjeta = (prod) => {
    const tieneFoto = prod.img && prod.img.startsWith('http');
    const contenedorImagen = tieneFoto 
      ? `<img src="${escaparAtributo(prod.img)}" alt="${escaparAtributo(prod.nombre)}" class="producto-tarjeta__img" loading="lazy" style="width: 100%; height: 100%; object-fit: contain;">`
      : `<div class="producto-tarjeta__placeholder">${prod.img || 'SUPPLEX'}</div>`;

    const descCorta = prod.desc ? `<p class="producto-tarjeta__variante" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${prod.desc}</p>` : '';

    return `
      <article class="producto-tarjeta tarjeta-sombra_efecto" 
               style="border: 1px solid var(--color-borde);"
               data-id="${escaparAtributo(prod.id)}"
               data-nombre="${escaparAtributo(prod.nombre)}"
               data-cat="${escaparAtributo(prod.cat || 'General')}"
               data-desc="${escaparAtributo(prod.desc || '')}"
               data-precio="${Number(prod.precio) || 0}"
               data-stock="${Number(prod.stock) || 10}"
               data-img="${escaparAtributo(prod.img || 'SUPPLEX')}">
        <div class="producto-tarjeta__imagen-caja">
          ${contenedorImagen}
        </div>
        <div class="producto-tarjeta__info">
          <span class="producto-tarjeta__marca">${prod.cat || 'General'}</span>
          <h3 class="producto-tarjeta__nombre" style="font-size: 0.95rem;">${prod.nombre}</h3>
          ${descCorta}
          <div class="producto-tarjeta__precios">
            <span class="producto-tarjeta__precio-actual">$ ${Number(prod.precio || 0).toLocaleString('es-AR')}</span>
          </div>
          <button type="button" class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${escaparAtributo(prod.nombre)}" data-precio="${Number(prod.precio) || 0}">AL CARRITO</button>
        </div>
      </article>
    `;
  };

  const renderizarCatalogo = (filtro) => {
    const grillaCatalogo = document.getElementById('grillaCatalogo');
    if (!grillaCatalogo || !Array.isArray(window.inventarioProductos)) return;
    
    grillaCatalogo.innerHTML = '';
    let productosFiltrados = window.inventarioProductos;
    
    if (filtro && filtro !== 'todos') {
      productosFiltrados = window.inventarioProductos.filter(prod => 
        (prod.cat && prod.cat.toLowerCase().includes(filtro.toLowerCase())) ||
        (prod.nombre && prod.nombre.toLowerCase().includes(filtro.toLowerCase()))
      );
    }

    if (productosFiltrados.length === 0) {
      grillaCatalogo.innerHTML = '<div class="catalogo__vacio" style="grid-column: 1/-1; text-align: center; padding: 2rem;">Aún no hay productos disponibles en esta categoría.</div>';
      return;
    }

    productosFiltrados.forEach(prod => {
      grillaCatalogo.insertAdjacentHTML('beforeend', crearHTMLTarjeta(prod));
    });
  };

  // Determinar ruta de PHP según la vista
  const rutaPHP = window.location.pathname.includes('/pages/') ? '../obtener_productos.php' : 'obtener_productos.php';

  fetch(rutaPHP)
    .then(respuesta => respuesta.json())
    .then(datosDelServidor => {
        if (!Array.isArray(datosDelServidor)) {
            console.error("Respuesta no esperada del servidor:", datosDelServidor);
            const grillaCatalogo = document.getElementById('grillaCatalogo');
            if (grillaCatalogo) {
                grillaCatalogo.innerHTML = `<div class="catalogo__vacio" style="grid-column: 1/-1; color: #ef4444; padding: 2rem; text-align: center;">Error al cargar inventario: ${datosDelServidor.error || 'Servidor no disponible'}</div>`;
            }
            return;
        }

        window.inventarioProductos = datosDelServidor;
        
        // 1. Carga la grilla del catálogo (en productos.html si existe)
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

        // 2. Renderiza productos únicamente en "Más Vendidos" (deja "carruselExplorar" intacto)
        const carruselDestacados = document.getElementById('carruselMasVendidos');
        if (carruselDestacados && window.inventarioProductos.length > 0) {
          carruselDestacados.innerHTML = '';
          const productosDestacados = window.inventarioProductos.slice(0, 10);
          productosDestacados.forEach(prod => {
            carruselDestacados.insertAdjacentHTML('beforeend', crearHTMLTarjeta(prod));
          });
        }

        // 3. Listeners para los botones de filtrado por categoría
        botonesFiltro.forEach(boton => {
          boton.addEventListener('click', (e) => {
            botonesFiltro.forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
            e.target.classList.add('catalogo__filtro-btn--activo');
            renderizarCatalogo(e.target.getAttribute('data-filtro'));
          });
        });
    })
    .catch(error => {
        console.warn("No se pudo cargar obtener_productos.php:", error);
        const grillaCatalogo = document.getElementById('grillaCatalogo');
        if (grillaCatalogo) {
            grillaCatalogo.innerHTML = '<div class="catalogo__vacio" style="grid-column: 1/-1; padding: 2rem; text-align: center;">No se pudo conectar con el catálogo de productos.</div>';
        }
    });

});

// =========================================
  // GESTIÓN DE CATÁLOGO Y FILTROS DINÁMICOS (BD)
  // =========================================
  window.inventarioProductos = [];
  let categoriaSeleccionada = 'todos';
  let marcaSeleccionada = 'todas';

  const renderizarCatalogoDinamico = () => {
    const grilla = document.getElementById('grillaCatalogo');
    const contadorDiv = document.getElementById('contadorVariantes');
    if (!grilla) return;

    const textoBusq = document.getElementById('filtroBuscador')?.value.toLowerCase().trim() || '';
    const presentacionFiltro = document.getElementById('filtroPresentacion')?.value || '';
    const saborFiltro = document.getElementById('filtroSabor')?.value || '';
    const ordenPrecio = document.getElementById('filtroOrdenPrecio')?.value || '';

    let filtrados = window.inventarioProductos.filter(prod => {
      const matchCat = (categoriaSeleccionada === 'todos' || prod.cat.toLowerCase() === categoriaSeleccionada.toLowerCase());
      const matchMarca = (marcaSeleccionada === 'todas' || prod.marca.toLowerCase() === marcaSeleccionada.toLowerCase());
      const matchPres = (presentacionFiltro === '' || prod.presentacion === presentacionFiltro);
      const matchSabor = (saborFiltro === '' || prod.sabor === saborFiltro);
      const matchTexto = textoBusq === '' || 
        prod.nombre.toLowerCase().includes(textoBusq) || 
        prod.marca.toLowerCase().includes(textoBusq) || 
        prod.cat.toLowerCase().includes(textoBusq) ||
        prod.presentacion.toLowerCase().includes(textoBusq) ||
        prod.sabor.toLowerCase().includes(textoBusq);

      return matchCat && matchMarca && matchPres && matchSabor && matchTexto;
    });

    if (ordenPrecio === 'menor') filtrados.sort((a, b) => a.precio - b.precio);
    if (ordenPrecio === 'mayor') filtrados.sort((a, b) => b.precio - a.precio);

    // Actualizar contador visual de variantes
    if (contadorDiv) {
      contadorDiv.textContent = `📦 Mostrando ${filtrados.length} de ${window.inventarioProductos.length} variantes cargadas en la base de datos`;
    }

    grilla.innerHTML = '';
    if (filtrados.length === 0) {
      grilla.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; background: #fff; border-radius: 12px;">No se encontraron productos con estos filtros.</div>';
      return;
    }

    filtrados.forEach(prod => {
      const tieneFoto = prod.img && prod.img.startsWith('http');
      const imgHTML = tieneFoto 
        ? `<img src="${prod.img}" alt="${prod.nombre}" style="width: 100%; height: 100%; object-fit: contain;">`
        : `<div class="producto-tarjeta__placeholder">SUPPLEX</div>`;

      const tarjeta = `
        <article class="producto-tarjeta tarjeta-sombra_efecto" style="border: 1px solid var(--color-borde); background: #fff; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;"
                 data-id="${prod.id}"
                 data-nombre="${prod.nombre} - ${prod.presentacion} (${prod.sabor})"
                 data-cat="${prod.cat}"
                 data-desc="${prod.desc}"
                 data-precio="${prod.precio}"
                 data-stock="${prod.stock}"
                 data-img="${prod.img}">
          <div class="producto-tarjeta__imagen-caja" style="height: 200px; background: #f4f4f5; display: flex; align-items: center; justify-content: center; position: relative;">
            ${imgHTML}
          </div>
          <div class="producto-tarjeta__info" style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1;">
            <span class="producto-tarjeta__marca" style="font-size: 0.75rem; font-weight: 700; color: #71717a; text-transform: uppercase;">${prod.cat} | ${prod.marca}</span>
            <h3 class="producto-tarjeta__nombre" style="font-size: 0.95rem; font-weight: 800; margin: 4px 0 4px;">${prod.nombre}</h3>
            <p style="font-size: 0.85rem; font-weight: 700; color: #27272a; margin-bottom: 4px;">${prod.presentacion}</p>
            <p style="font-size: 0.8rem; color: #52525b; margin-bottom: 12px;">Sabor: ${prod.sabor || 'N/D'}</p>
            <div class="producto-tarjeta__precios" style="margin-top: auto; margin-bottom: 12px;">
              <span class="producto-tarjeta__precio-actual" style="font-size: 1.15rem; font-weight: 900;">$ ${Number(prod.precio).toLocaleString('es-AR')}</span>
            </div>
            <button type="button" class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${prod.nombre} - ${prod.presentacion}" data-precio="${prod.precio}" style="width: 100%; padding: 10px; background: #111; color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer;">AL CARRITO</button>
          </div>
        </article>
      `;
      grilla.insertAdjacentHTML('beforeend', tarjeta);
    });
  };

  // Cargar datos del servidor y poblar filtros dinámicamente
  const rutaPHP = window.location.pathname.includes('/pages/') ? '../obtener_productos.php' : 'obtener_productos.php';

  fetch(rutaPHP + '?nocache=' + Date.now())
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) return;
      window.inventarioProductos = data;

      // Poblar Categorías dinámicas en el aside
      const listaCat = document.getElementById('listaCategoriasBD');
      const categoriasUnicas = [...new Set(data.map(p => p.cat))].filter(Boolean);
      if (listaCat) {
        categoriasUnicas.forEach(cat => {
          listaCat.insertAdjacentHTML('beforeend', `<li><button class="catalogo__filtro-btn" data-cat="${cat}">${cat}</button></li>`);
        });
      }

      // Poblar Marcas dinámicas en el aside
      const listaMarca = document.getElementById('listaMarcasBD');
      const marcasUnicas = [...new Set(data.map(p => p.marca))].filter(Boolean);
      if (listaMarca) {
        marcasUnicas.forEach(marca => {
          listaMarca.insertAdjacentHTML('beforeend', `<li><button class="catalogo__filtro-btn" data-marca="${marca}">${marca}</button></li>`);
        });
      }

      // Poblar Selects de Presentación y Sabor
      const selectPres = document.getElementById('filtroPresentacion');
      const presUnicas = [...new Set(data.map(p => p.presentacion))].filter(Boolean);
      presUnicas.forEach(p => selectPres?.insertAdjacentHTML('beforeend', `<option value="${p}">${p}</option>`));

      const selectSabor = document.getElementById('filtroSabor');
      const saboresUnicos = [...new Set(data.map(p => p.sabor))].filter(Boolean);
      saboresUnicos.forEach(s => selectSabor?.insertAdjacentHTML('beforeend', `<option value="${s}">${s}</option>`));

      renderizarCatalogoDinamico();

      // Listeners de filtros superiores
      document.getElementById('filtroBuscador')?.addEventListener('input', renderizarCatalogoDinamico);
      document.getElementById('filtroPresentacion')?.addEventListener('change', renderizarCatalogoDinamico);
      document.getElementById('filtroSabor')?.addEventListener('change', renderizarCatalogoDinamico);
      document.getElementById('filtroOrdenPrecio')?.addEventListener('change', renderizarCatalogoDinamico);

      // Clic en categorías (barra lateral)
      document.querySelectorAll('#listaCategoriasBD button').forEach(btn => {
        btn.addEventListener('click', (e) => {
          document.querySelectorAll('#listaCategoriasBD button').forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
          e.target.classList.add('catalogo__filtro-btn--activo');
          categoriaSeleccionada = e.target.getAttribute('data-cat');
          renderizarCatalogoDinamico();
        });
      });

      // Clic en marcas (barra lateral)
      document.querySelectorAll('#listaMarcasBD button').forEach(btn => {
        btn.addEventListener('click', (e) => {
          document.querySelectorAll('#listaMarcasBD button').forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
          e.target.classList.add('catalogo__filtro-btn--activo');
          marcaSeleccionada = e.target.getAttribute('data-marca');
          renderizarCatalogoDinamico();
        });
      });
    });