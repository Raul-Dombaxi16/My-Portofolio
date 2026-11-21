function fecharCard() {
    const cardErro = document.getElementById("cardErro");

    if (cardErro) {
        cardErro.style.display = "none";
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const cardErro = document.getElementById("cardErro");

    if (cardErro && window.location.search.includes("erro=nao_encontrado")) {
        history.replaceState(null, "", window.location.pathname);
    }
});
