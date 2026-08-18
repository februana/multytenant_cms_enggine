const parallax = document.getElementById("home-img-lg");
const parallax1 = document.getElementById("parallax1");
const parallax2 = document.getElementById("parallax2");

window.addEventListener("scroll", function()
{
    let offset = window.pageYOffset;
    if (parallax) {
        parallax.style.backgroundPositionX = offset*(-0.3)-100 + "px";
    }
})


window.addEventListener("scroll", function()
{
    let offset = window.pageYOffset;
    offset-=3100;
    if (parallax1) {
        parallax1.style.backgroundPositionY = offset*(0.1) + "px";
    }
})

window.addEventListener("scroll", function()
{
    let offset = window.pageYOffset;
    offset-=4800;
    if (parallax2) {
        parallax2.style.backgroundPositionY = offset*(-0.1) + "px";
    }
})

function myFunction() {
    const checkbox = document.getElementById("check");
    if (checkbox) {
        checkbox.checked = false;
    }
}

function reveal() {
    const reveals = document.querySelectorAll(".reveal");

    for (let i = 0; i < reveals.length; i++) {
        const windowHeight = window.innerHeight;
        const elementTop = reveals[i].getBoundingClientRect().top;
        const elementVisible = 150;

        if (elementTop < windowHeight - elementVisible) {
            reveals[i].classList.add("active");
        } else {
            reveals[i].classList.remove("active");
        }
    }
}

window.addEventListener("scroll", reveal);
window.addEventListener("load", reveal);
if (document.readyState !== "loading") {
    reveal();
} else {
    document.addEventListener("DOMContentLoaded", reveal, { once: true });
}
