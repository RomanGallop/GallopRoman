const equipos = { A: [], B: [] };
const statsEquipos = {
  A: { ataque: 0, defensa: 0 },
  B: { ataque: 0, defensa: 0 },
};
const tiradasDados = { A: [], B: [] };

const cargarPokemons = async () => {
  for (let equipo of ["A", "B"]) {
    for (let i = 0; i < 3; i++) {
      const id = Math.floor(Math.random() * 151) + 1;
      const res = await fetch(`https://pokeapi.co/api/v2/pokemon/${id}`);
      const data = await res.json();
      equipos[equipo].push(data);
      statsEquipos[equipo].ataque += data.stats[1].base_stat;
      statsEquipos[equipo].defensa += data.stats[2].base_stat;
      document.getElementById(
        `equipo${equipo}`
      ).innerHTML += `<img src="${data.sprites.front_default}" alt="${data.name}">`;
    }
  }
};

const tirarDados = (equipo) => {
  if (tiradasDados[equipo].length < 3) {
    const dado1 = Math.floor(Math.random() * 6) + 1;
    const dado2 = Math.floor(Math.random() * 6) + 1;
    const suma = dado1 + dado2;
    tiradasDados[equipo].push(suma);
    document.getElementById(
      `dados${equipo}`
    ).innerHTML += `<p>Tirada ${tiradasDados[equipo].length}: ${suma}</p>`;

    if (tiradasDados[equipo].length === 3) {
      document.getElementById(`btnDados${equipo}`).disabled = true;
    }

    if (tiradasDados.A.length === 3 && tiradasDados.B.length === 3) {
      document.getElementById("batalla").disabled = false;
    }
  }
};

const iniciarBatalla = () => {
  const diffA = statsEquipos.A.ataque - statsEquipos.B.defensa;
  const diffB = statsEquipos.B.ataque - statsEquipos.A.defensa;
  let ganador, mensaje;

  if (diffA > diffB) ganador = "Equipo A";
  else if (diffB > diffA) ganador = "Equipo B";
  else {
    const maxA = Math.max(...tiradasDados.A);
    const maxB = Math.max(...tiradasDados.B);
    ganador = maxA > maxB ? "Equipo A" : "Equipo B";
    mensaje = `Empate por batalla, desempate por dados: Equipo A (${maxA}), Equipo B (${maxB}). Ganador: ${ganador}`;
  }

  if (!mensaje) mensaje = `Ganador: ${ganador}`;

  document.getElementById("resultado").innerHTML = `
        ${mensaje}<br><br>
        Ataque Equipo A: ${statsEquipos.A.ataque}<br> Defensa Equipo B: ${statsEquipos.B.defensa} (Diferencia: ${diffA})<br><br>
        Ataque Equipo B: ${statsEquipos.B.ataque}<br> Defensa Equipo A: ${statsEquipos.A.defensa} (Diferencia: ${diffB})
    `;
};

document.getElementById("btnDadosA").onclick = () => tirarDados("A");
document.getElementById("btnDadosB").onclick = () => tirarDados("B");
document.getElementById("batalla").onclick = iniciarBatalla;

window.onload = cargarPokemons;
