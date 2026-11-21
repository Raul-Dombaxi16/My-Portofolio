let h=document.querySelectorAll(`.date-portofolio`);

h.forEach(function(a){
 a.textContent= new Date().getFullYear();


})
