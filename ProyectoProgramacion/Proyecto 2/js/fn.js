const persona1 = {
  nombre: "roman",
  apellido: "gallop",
  edad: 19,
  dni: 46603146,
  colores: ["rojo", "azul", "amarillo"],
};

const persona2 = {
  nombre: "javier",
  apellido: "ibarra",
  edad: 46,
  dni: 10600200,
  colores: ["marron", "bordo", "negro"],
};

const color = "amarillo";

function suma(persona1, persona2) {
  if (persona1.edad > persona2.edad) {
    console.log(" La persona " + persona1.nombre + " es mas grande");

    let favorito = false;

    for (let i = 0; i < persona1.colores.length; i++) {
      if (persona1.colores[i] === color) {
        favorito = true;
      }
    }
    if (favorito) {
      console.log(
        " A la persona " + persona1.nombre + " le gusta el color amarillo."
      );
    } else {
      console.log(
        "A la persona " + persona1.nombre + " no le gusta el color amarillo."
      );
    }
  } else if (persona2.edad > persona1.edad) {
    console.log(" La persona " + persona2.nombre + " es mas grande");

    let favorito2 = false;

    for (let i = 0; i < persona2.colores.length; i++) {
      if (persona2.colores[i] === color) {
        favorito2 = true;
      }
    }
    if (favorito2) {
      console.log(
        " A la persona " + persona2.nombre + " le gusta el color amarillo."
      );
    } else {
      console.log(
        "A la persona " + persona2.nombre + " no le gusta el color amarillo."
      );
    }
  }
}

suma(persona1, persona2);
