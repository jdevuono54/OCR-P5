$(document).ready(() => {
  function onKonamiCode(cb) {
    let input = "";
    const key = "38384040373937396665";

    document.addEventListener("keydown", (e) => {
      input += (`${e.keyCode}`);

      if (input === key) {
        return cb();
      }

      if (!key.indexOf(input)) { return 1; }

      input = (`${e.keyCode}`);

      return 1;
    });
  }

  onKonamiCode(() => {
    const img = new Image();

    img.src = `../images/konami/k${Math.floor(Math.random() * 5)}.webp`;
    img.style.width = "350px";
    img.style.height = "300px";
    img.style.transition = "6s all linear";
    img.style.position = "fixed";
    img.style.left = "-400px";
    img.style.bottom = "-5px";
    img.style.zIndex = 999999;

    document.body.appendChild(img);

    window.setTimeout(() => {
      img.style.left = "calc(100% + 500px)";
    }, 50);

    window.setTimeout(() => {
      img.parentNode.removeChild(img);
    }, 6000);
  });
});
