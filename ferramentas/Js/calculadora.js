

let numero = document.querySelectorAll(".btn");
let m = document.querySelector(".display");
let AC = document.querySelector("#Eliminar");
let igual = document.querySelector(".igual");
let alterar = document.querySelector("#alterar");
let operadores = document.querySelectorAll(".operadores");
let percentagem=document.querySelector("#porcentos")
let resultado=0



AC.addEventListener("click", function() {
  primeiro = ""
  segundo = ""
  opm = ""
  m.textContent = ""
})

function mudar() {
  if (opm == "") {
    primeiro = "" + (Number(primeiro) * -1)
  } else {
    segundo = "" + (Number(segundo) * -1)
  }
  m.textContent = primeiro + opm + segundo
}

alterar.addEventListener("click", mudar);

// percentagem.addEventListener("click",function () {

// })
