document.addEventListener('DOMContentLoaded', () => {

  
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


  const trackHero = document.getElementById('trackHero');
  const btnAntHero = document.getElementById('btnAntHero');
  const btnSigHero = document.getElementById('btnSigHero');

  if (trackHero && btnAntHero && btnSigHero) {
    btnSigHero.addEventListener('click', () => {
      
      trackHero.scrollBy({ left: window.innerWidth, behavior: 'smooth' });
    });
    btnAntHero.addEventListener('click', () => {
      
      trackHero.scrollBy({ left: -window.innerWidth, behavior: 'smooth' });
    });
  }