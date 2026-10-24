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
  // 4. SISTEMA DE CARRITO (CON LOCALSTORAGE, CUPONES Y WSP)
  // =========================================
  const numeroWhatsApp = "5491100000000"; 
  let carrito = [];
  try {
    const guardado = localStorage.getItem('supplex_carrito');
    if (guardado) carrito = JSON.parse(guardado);
  } catch (err) {
    carrito = [];
  }

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

  const guardarCarritoLocal = () => {
    try {
      localStorage.setItem('supplex_carrito', JSON.stringify(carrito));
    } catch (e) {}
  };

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
      if (msjCupon) {
        msjCupon.textContent = `¡Cupón ${codigo} (${descuentoPorcentaje}% OFF) aplicado!`;
        msjCupon.style.color = "#25d366";
        msjCupon.style.display = "block";
      }
    } else {
      cuponAplicado = null;
      descuentoPorcentaje = 0;
      if (msjCupon) {
        msjCupon.textContent = "Cupón inválido.";
        msjCupon.style.color = "red";
        msjCupon.style.display = "block";
      }
    }
    renderizarCarrito();
  });

  const renderizarCarrito = () => {
    let cantidadTotal = 0;
    let subtotal = 0;

    carrito.forEach(item => {
      cantidadTotal += item.cantidad;
      subtotal += (Number(item.precio) || 0) * item.cantidad;
    });

    if (contadorCarrito) contadorCarrito.textContent = String(cantidadTotal);
    if (!listaCarrito) return;

    listaCarrito.innerHTML = '';

    if (carrito.length === 0) {
      listaCarrito.innerHTML = '<p class="carrito-modal__vacio">Tu carrito está vacío.</p>';
      if (cartSubtotal) cartSubtotal.textContent = "$ 0";
      if (lineaDescuento) lineaDescuento.style.display = "none";
      if (totalCarrito) totalCarrito.textContent = "$ 0";
      return;
    }

    carrito.forEach((item, index) => {
      let totalItem = (Number(item.precio) || 0) * item.cantidad;

      const divItem = document.createElement('div');
      divItem.classList.add('carrito-item');
      divItem.innerHTML = `
        <div class="carrito-item__info" style="flex-direction: row; align-items: center;">
          <span class="carrito-item__cant">${item.cantidad}x</span>
          <div style="display: flex; flex-direction: column;">
            <span class="carrito-item__nombre">${item.nombre}</span>
            <span class="carrito-item__precio">$ ${totalItem.toLocaleString('es-AR')}</span>
          </div>
        </div>
        <button type="button" class="carrito-item__eliminar" onclick="window.eliminarDelCarrito(${index})">×</button>
      `;
      listaCarrito.appendChild(divItem);
    });

    let descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    let totalF = subtotal - descuentoMonto;

    if (cartSubtotal) cartSubtotal.textContent = `$ ${subtotal.toLocaleString('es-AR')}`;
    if (totalCarrito) totalCarrito.textContent = `$ ${totalF.toLocaleString('es-AR')}`;

    if (descuentoMonto > 0 && lineaDescuento) {
      lineaDescuento.style.display = "flex";
      if (cartNombreCupon) cartNombreCupon.textContent = cuponAplicado;
      if (cartMontoDescuento) cartMontoDescuento.textContent = `-$ ${descuentoMonto.toLocaleString('es-AR')}`;
    } else if (lineaDescuento) {
      lineaDescuento.style.display = "none";
    }
  };

  window.eliminarDelCarrito = (index) => {
    carrito.splice(index, 1);
    guardarCarritoLocal();
    renderizarCarrito();
    animarContador();
  };

  const animarContador = () => {
    if (contadorCarrito) {
      contadorCarrito.style.transform = 'scale(1.5)';
      setTimeout(() => contadorCarrito.style.transform = 'scale(1)', 200);
    }
  };

  renderizarCarrito();

  // FINALIZAR COMPRA: ENVÍO AL SERVIDOR Y WSP
  btnEnviarWhatsApp?.addEventListener('click', (e) => {
    e.preventDefault();
    if (carrito.length === 0) {
      alert("⚠️ Tu carrito está vacío. Agregá al menos un producto.");
      return;
    }

    const nombreCliente = document.getElementById('cartNombre')?.value.trim() || '';
    const direccionCliente = document.getElementById('cartDireccion')?.value.trim() || '';

    if (!nombreCliente || !direccionCliente) {
      alert("⚠️ Por favor, completá tu Nombre y Dirección de Envío para procesar el pedido.");
      return;
    }

    const orderId = "PED-" + Math.floor(Math.random() * 90000 + 10000); 
    const fecha = new Date().toLocaleDateString('es-AR');
    
    let subtotal = 0;
    carrito.forEach(item => subtotal += ((Number(item.precio) || 0) * item.cantidad));
    let descuentoMonto = subtotal * (descuentoPorcentaje / 100);
    let totalFinal = subtotal - descuentoMonto;

    let msjWsp = `*NUEVO PEDIDO: ${orderId}*\n📅 Fecha: ${fecha}\n👤 Cliente: ${nombreCliente}\n📍 Dirección: ${direccionCliente}\n---------------------------\n*ARTÍCULOS:*\n`;
    carrito.forEach(item => {
      msjWsp += `🔸 ${item.cantidad}x ${item.nombre} ($ ${((Number(item.precio) || 0) * item.cantidad).toLocaleString('es-AR')})\n`;
    });
    msjWsp += `---------------------------\nSubtotal: $ ${subtotal.toLocaleString('es-AR')}\n`;
    if (descuentoMonto > 0) msjWsp += `Descuento (${cuponAplicado}): -$ ${descuentoMonto.toLocaleString('es-AR')}\n`;
    msjWsp += `*TOTAL A PAGAR: $ ${totalFinal.toLocaleString('es-AR')}*\n\nPor favor, confirmame los métodos de pago. ¡Gracias!`;

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
    .catch(() => {
      console.warn("Guardando comprobante y abriendo WhatsApp...");
    })
    .finally(() => {
      window.open(`https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(msjWsp)}`, '_blank');
      reiniciarFormularioCarrito();
    });

    function reiniciarFormularioCarrito() {
      carrito = [];
      guardarCarritoLocal();
      cuponAplicado = null;
      descuentoPorcentaje = 0;
      const inputNom = document.getElementById('cartNombre');
      const inputDir = document.getElementById('cartDireccion');
      if (inputNom) inputNom.value = "";
      if (inputDir) inputDir.value = "";
      if (inputCupon) inputCupon.value = "";
      if (msjCupon) msjCupon.style.display = "none";
      btnEnviarWhatsApp.textContent = "FINALIZAR COMPRA";
      cerrarCarrito();
      renderizarCarrito();
      animarContador();
    }
  });

  // =========================================
  // 5. EVENTOS GLOBALES (AGREGAR AL CARRITO / CARD FLOTANTE)
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
    // A) Botón "AL CARRITO"
    const botonAgregar = e.target.closest('.js-agregar-carrito');
    if (botonAgregar) {
      e.preventDefault();
      e.stopPropagation();

      const nombre = botonAgregar.getAttribute('data-nombre') || 'Suplemento Supplex';
      const precioRaw = botonAgregar.getAttribute('data-precio');
      const precio = Number.isFinite(parseFloat(precioRaw)) ? parseFloat(precioRaw) : 0;

      const productoExistente = carrito.find(item => item.nombre === nombre && Number(item.precio) === precio);
      if (productoExistente) {
        productoExistente.cantidad++;
      } else {
        carrito.push({ nombre, precio, cantidad: 1 });
      }
      
      guardarCarritoLocal();
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

    // B) Abrir Card Flotante
    const clicEnTarjeta = e.target.closest('.producto-tarjeta, .combo-tarjeta');
    const clicEnFavorito = e.target.closest('.producto-tarjeta__favorito');

    if (clicEnTarjeta && !clicEnFavorito && productoModal) {
      e.preventDefault();

      const idTarjeta = clicEnTarjeta.getAttribute('data-id');
      const btnTarjeta = clicEnTarjeta.querySelector('.js-agregar-carrito');
      const elementoNombre = clicEnTarjeta.querySelector('.producto-tarjeta__nombre, .combo-tarjeta__nombre');
      const nombreBusqueda = elementoNombre ? elementoNombre.textContent.trim() : '';

      let productoData = null;

      if (Array.isArray(window.inventarioProductos) && window.inventarioProductos.length > 0 && idTarjeta) {
        productoData = window.inventarioProductos.find(p => String(p.id) === String(idTarjeta));
      }

      const nombreMostrar = clicEnTarjeta.getAttribute('data-nombre') || (productoData ? armarNombreCompleto(productoData) : nombreBusqueda) || 'Suplemento Deportivo';
      const catMostrar = clicEnTarjeta.getAttribute('data-cat') || (productoData ? `${productoData.cat} | ${productoData.marca}` : 'General');
      const descMostrar = clicEnTarjeta.getAttribute('data-desc') || (productoData ? productoData.desc : 'Suplemento deportivo de alta calidad.');
      const precioMostrar = parseFloat(clicEnTarjeta.getAttribute('data-precio') ?? productoData?.precio ?? btnTarjeta?.getAttribute('data-precio') ?? 0) || 0;
      const stockMostrar = parseInt(clicEnTarjeta.getAttribute('data-stock') ?? productoData?.stock ?? 0, 10);
      const imgMostrar = clicEnTarjeta.getAttribute('data-img') || productoData?.img || 'SUPPLEX';

      if (modalProdImg) {
        const tieneFoto = imgMostrar && imgMostrar.startsWith('http');
        modalProdImg.innerHTML = tieneFoto 
          ? `<img src="${imgMostrar}" alt="${nombreMostrar}" style="width: 100%; height: 100%; object-fit: contain;">`
          : `<div class="producto-tarjeta__placeholder">SUPPLEX</div>`;
      }
      if (modalProdCat) modalProdCat.textContent = catMostrar;
      if (modalProdTitulo) modalProdTitulo.textContent = nombreMostrar;
      if (modalProdDesc) modalProdDesc.textContent = descMostrar;
      if (modalProdStock) {
        modalProdStock.textContent = `✔ Stock registrado en sistema: ${Number.isNaN(stockMostrar) ? 0 : stockMostrar} unidades`;
        modalProdStock.style.color = '#22c55e';
      }
      if (modalProdPrecio) {
        modalProdPrecio.textContent = `$ ${Number(precioMostrar).toLocaleString('es-AR')}`;
      }
      if (modalProdBtn) {
        modalProdBtn.setAttribute('data-nombre', nombreMostrar);
        modalProdBtn.setAttribute('data-precio', String(precioMostrar));
      }

      productoModal.classList.add('producto-modal--activo');
    }
  });

  // =========================================
  // 6. BUSCADOR A PANTALLA COMPLETA (LUPA DEL HEADER)
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
    inputBuscador.addEventListener('input', (e) => {
      clearTimeout(temporizadorBuscador);
      temporizadorBuscador = setTimeout(() => {
        const textoBusqueda = e.target.value.toLowerCase().trim();
        if (textoBusqueda === '') {
          resultadosBuscador.innerHTML = '<p class="buscador-modal__vacio">Escribí arriba para buscar productos.</p>';
          return;
        }

        if (Array.isArray(window.inventarioProductos)) {
          let resultados = window.inventarioProductos.filter(prod => 
            (prod.nombre && prod.nombre.toLowerCase().includes(textoBusqueda)) || 
            (prod.marca && prod.marca.toLowerCase().includes(textoBusqueda)) ||
            (prod.cat && prod.cat.toLowerCase().includes(textoBusqueda)) ||
            (prod.sabor && prod.sabor.toLowerCase().includes(textoBusqueda)) ||
            (prod.presentacion && prod.presentacion.toLowerCase().includes(textoBusqueda))
          );

          const resultadosLimitados = resultados.slice(0, 16);
          resultadosBuscador.innerHTML = '';

          if (resultadosLimitados.length === 0) {
            resultadosBuscador.innerHTML = `<p class="buscador-modal__vacio">No encontramos nada para "<strong>${textoBusqueda}</strong>".</p>`;
          } else {
            resultadosLimitados.forEach(prod => {
              resultadosBuscador.insertAdjacentHTML('beforeend', crearHTMLTarjeta(prod));
            });
          }
        }
      }, 200);
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
  // 8. CATÁLOGO Y FILTROS DINÁMICOS CONECTADOS A LA BD
  // =========================================
  window.inventarioProductos = [];
  let categoriaSeleccionada = 'todos';
  let marcaSeleccionada = 'todas';

  const escaparAtributo = (texto) => String(texto ?? '').replace(/"/g, '&quot;');

  const armarNombreCompleto = (prod) => {
    let titulo = prod.nombre || 'Suplemento';
    if (prod.presentacion) titulo += ` - ${prod.presentacion}`;
    if (prod.sabor) titulo += ` (${prod.sabor})`;
    return titulo;
  };

  const crearHTMLTarjeta = (prod) => {
    const tieneFoto = prod.img && prod.img.startsWith('http');
    const imgHTML = tieneFoto 
      ? `<img src="${escaparAtributo(prod.img)}" alt="${escaparAtributo(prod.nombre)}" class="producto-tarjeta__img" loading="lazy" style="width: 100%; height: 100%; object-fit: contain;">`
      : `<div class="producto-tarjeta__placeholder">SUPPLEX</div>`;

    const nombreCarrito = armarNombreCompleto(prod);
    const precioNum = Number(prod.precio) || 0;

    return `
      <article class="producto-tarjeta tarjeta-sombra_efecto" 
               style="border: 1px solid var(--color-borde); background: #fff; border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;"
               data-id="${escaparAtributo(prod.id)}"
               data-nombre="${escaparAtributo(nombreCarrito)}"
               data-cat="${escaparAtributo((prod.cat || 'General') + ' | ' + (prod.marca || 'Genérica'))}"
               data-desc="${escaparAtributo(prod.desc || '')}"
               data-precio="${precioNum}"
               data-stock="${Number(prod.stock) || 0}"
               data-img="${escaparAtributo(prod.img || 'SUPPLEX')}">
        <div class="producto-tarjeta__imagen-caja" style="height: 200px; background: #f4f4f5; display: flex; align-items: center; justify-content: center; position: relative;">
          ${imgHTML}
        </div>
        <div class="producto-tarjeta__info" style="padding: 16px; display: flex; flex-direction: column; flex-grow: 1;">
          <span class="producto-tarjeta__marca" style="font-size: 0.75rem; font-weight: 700; color: #71717a; text-transform: uppercase;">${prod.cat || 'General'} | ${prod.marca || 'Genérica'}</span>
          <h3 class="producto-tarjeta__nombre" style="font-size: 0.95rem; font-weight: 800; margin: 4px 0 4px;">${prod.nombre}</h3>
          <p style="font-size: 0.82rem; font-weight: 700; color: #27272a; margin-bottom: 4px;">${prod.presentacion || 'Estándar'} ${prod.sabor ? '• ' + prod.sabor : ''}</p>
          <div class="producto-tarjeta__precios" style="margin-top: auto; margin-bottom: 12px;">
            <span class="producto-tarjeta__precio-actual" style="font-size: 1.15rem; font-weight: 900;">$ ${precioNum.toLocaleString('es-AR')}</span>
          </div>
          <button type="button" class="producto-tarjeta__boton boton-oscuro_efecto js-agregar-carrito" data-nombre="${escaparAtributo(nombreCarrito)}" data-precio="${precioNum}">AL CARRITO</button>
        </div>
      </article>
    `;
  };

  const renderizarCatalogoDinamico = () => {
    const grilla = document.getElementById('grillaCatalogo');
    const contadorDiv = document.getElementById('contadorVariantes');
    if (!grilla) return;

    const textoBusq = document.getElementById('filtroBuscador')?.value.toLowerCase().trim() || '';
    const presentacionFiltro = document.getElementById('filtroPresentacion')?.value || '';
    const saborFiltro = document.getElementById('filtroSabor')?.value || '';
    const ordenPrecio = document.getElementById('filtroOrdenPrecio')?.value || '';

    let filtrados = window.inventarioProductos.filter(prod => {
      const matchCat = (categoriaSeleccionada === 'todos' || (prod.cat && prod.cat.toLowerCase().includes(categoriaSeleccionada.toLowerCase())));
      const matchMarca = (marcaSeleccionada === 'todas' || (prod.marca && prod.marca.toLowerCase() === marcaSeleccionada.toLowerCase()));
      const matchPres = (presentacionFiltro === '' || prod.presentacion === presentacionFiltro);
      const matchSabor = (saborFiltro === '' || prod.sabor === saborFiltro);
      const matchTexto = textoBusq === '' || 
        (prod.nombre && prod.nombre.toLowerCase().includes(textoBusq)) || 
        (prod.marca && prod.marca.toLowerCase().includes(textoBusq)) || 
        (prod.cat && prod.cat.toLowerCase().includes(textoBusq)) ||
        (prod.presentacion && prod.presentacion.toLowerCase().includes(textoBusq)) ||
        (prod.sabor && prod.sabor.toLowerCase().includes(textoBusq));

      return matchCat && matchMarca && matchPres && matchSabor && matchTexto;
    });

    if (ordenPrecio === 'menor') {
      filtrados.sort((a, b) => (Number(a.precio) || 0) - (Number(b.precio) || 0));
    } else if (ordenPrecio === 'mayor') {
      filtrados.sort((a, b) => (Number(b.precio) || 0) - (Number(a.precio) || 0));
    }

    if (contadorDiv) {
      contadorDiv.textContent = `📦 Mostrando ${filtrados.length} de ${window.inventarioProductos.length} variantes activas en la base de datos`;
    }

    grilla.innerHTML = '';
    if (filtrados.length === 0) {
      grilla.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 3rem; background: #fff; border-radius: 12px;">No se encontraron productos con estos filtros.</div>';
      return;
    }

    const htmlAcumulado = filtrados.map(prod => crearHTMLTarjeta(prod)).join('');
    grilla.innerHTML = htmlAcumulado;
  };

  const rutaPHP = window.location.pathname.includes('/pages/') ? '../obtener_productos.php' : 'obtener_productos.php';

  fetch(rutaPHP + '?nocache=' + Date.now())
    .then(res => res.json())
    .then(data => {
      if (!Array.isArray(data)) {
        const grilla = document.getElementById('grillaCatalogo');
        if (grilla) {
          grilla.innerHTML = `<div style="grid-column: 1/-1; color: #ef4444; padding: 2rem; text-align: center; background: #fff; border-radius: 12px;">Error al cargar inventario: ${data?.error || data?.error_python || 'Servidor no disponible'}</div>`;
        }
        return;
      }

      window.inventarioProductos = data;

      // 1. Poblar carrusel "Lo Más Vendido" en index.html
      const carruselDestacados = document.getElementById('carruselMasVendidos');
      if (carruselDestacados && data.length > 0) {
        carruselDestacados.innerHTML = '';
        const conPrecio = data.filter(p => Number(p.precio) > 0);
        const destacados = (conPrecio.length > 0 ? conPrecio : data).slice(0, 10);
        carruselDestacados.innerHTML = destacados.map(prod => crearHTMLTarjeta(prod)).join('');
      }

      // 2. Poblar Categorías dinámicas en productos.html
      const listaCat = document.getElementById('listaCategoriasBD');
      const categoriasUnicas = [...new Set(data.map(p => p.cat))].filter(Boolean);
      if (listaCat) {
        listaCat.innerHTML = '<li><button type="button" class="catalogo__filtro-btn catalogo__filtro-btn--activo" data-cat="todos">Todos los productos</button></li>';
        categoriasUnicas.forEach(cat => {
          listaCat.insertAdjacentHTML('beforeend', `<li><button type="button" class="catalogo__filtro-btn" data-cat="${escaparAtributo(cat)}">${cat}</button></li>`);
        });
      }

      // 3. Poblar Marcas dinámicas en productos.html
      const listaMarca = document.getElementById('listaMarcasBD');
      const marcasUnicas = [...new Set(data.map(p => p.marca))].filter(Boolean);
      if (listaMarca) {
        listaMarca.innerHTML = '<li><button type="button" class="catalogo__filtro-btn catalogo__filtro-btn--activo" data-marca="todas">Todas las marcas</button></li>';
        marcasUnicas.forEach(marca => {
          listaMarca.insertAdjacentHTML('beforeend', `<li><button type="button" class="catalogo__filtro-btn" data-marca="${escaparAtributo(marca)}">${marca}</button></li>`);
        });
      }

      // 4. Poblar Selects de Presentación y Sabor
      const selectPres = document.getElementById('filtroPresentacion');
      if (selectPres) {
        const presUnicas = [...new Set(data.map(p => p.presentacion))].filter(Boolean);
        presUnicas.forEach(p => selectPres.insertAdjacentHTML('beforeend', `<option value="${escaparAtributo(p)}">${p}</option>`));
      }

      const selectSabor = document.getElementById('filtroSabor');
      if (selectSabor) {
        const saboresUnicos = [...new Set(data.map(p => p.sabor))].filter(Boolean);
        saboresUnicos.forEach(s => selectSabor.insertAdjacentHTML('beforeend', `<option value="${escaparAtributo(s)}">${s}</option>`));
      }

      const urlParams = new URLSearchParams(window.location.search);
      const catUrl = urlParams.get('cat');
      if (catUrl) {
        categoriaSeleccionada = catUrl;
        document.querySelectorAll('#listaCategoriasBD button').forEach(b => {
          const valorBtn = b.getAttribute('data-cat') || '';
          const coincide = valorBtn !== 'todos' && valorBtn.toLowerCase().includes(catUrl.toLowerCase());
          b.classList.toggle('catalogo__filtro-btn--activo', coincide);
        });
      }

      renderizarCatalogoDinamico();

      // Listeners
      document.getElementById('filtroBuscador')?.addEventListener('input', renderizarCatalogoDinamico);
      document.getElementById('filtroPresentacion')?.addEventListener('change', renderizarCatalogoDinamico);
      document.getElementById('filtroSabor')?.addEventListener('change', renderizarCatalogoDinamico);
      document.getElementById('filtroOrdenPrecio')?.addEventListener('change', renderizarCatalogoDinamico);

      document.getElementById('btnLimpiarFiltros')?.addEventListener('click', () => {
        categoriaSeleccionada = 'todos';
        marcaSeleccionada = 'todas';
        const busq = document.getElementById('filtroBuscador');
        const pres = document.getElementById('filtroPresentacion');
        const sab = document.getElementById('filtroSabor');
        const ord = document.getElementById('filtroOrdenPrecio');
        if (busq) busq.value = '';
        if (pres) pres.value = '';
        if (sab) sab.value = '';
        if (ord) ord.value = '';

        document.querySelectorAll('#listaCategoriasBD button').forEach(b => {
          b.classList.toggle('catalogo__filtro-btn--activo', b.getAttribute('data-cat') === 'todos');
        });
        document.querySelectorAll('#listaMarcasBD button').forEach(b => {
          b.classList.toggle('catalogo__filtro-btn--activo', b.getAttribute('data-marca') === 'todas');
        });
        renderizarCatalogoDinamico();
      });

      document.querySelectorAll('#listaCategoriasBD button').forEach(btn => {
        btn.addEventListener('click', (e) => {
          document.querySelectorAll('#listaCategoriasBD button').forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
          e.currentTarget.classList.add('catalogo__filtro-btn--activo');
          categoriaSeleccionada = e.currentTarget.getAttribute('data-cat') || 'todos';
          renderizarCatalogoDinamico();
        });
      });

      document.querySelectorAll('#listaMarcasBD button').forEach(btn => {
        btn.addEventListener('click', (e) => {
          document.querySelectorAll('#listaMarcasBD button').forEach(b => b.classList.remove('catalogo__filtro-btn--activo'));
          e.currentTarget.classList.add('catalogo__filtro-btn--activo');
          marcaSeleccionada = e.currentTarget.getAttribute('data-marca') || 'todas';
          renderizarCatalogoDinamico();
        });
      });
    })
    .catch(err => {
      console.warn("No se pudo conectar con obtener_productos.php:", err);
      const grilla = document.getElementById('grillaCatalogo');
      if (grilla) {
        grilla.innerHTML = '<div style="grid-column: 1/-1; padding: 2rem; text-align: center; background: #fff; border-radius: 12px;">No se pudo conectar con el catálogo de productos.</div>';
      }
    });

});