window.addEventListener('load', initTutorial);

function initTutorial(e) {
  if (document.getElementsByClassName('editor').length != 0) {
    MicroModal.init();

    if (tutorial) {
      MicroModal.show('tutorial_1');

      let xhr_object = new XMLHttpRequest();
      xhr_object.open('PATCH', base_url + 'organizer/checkTutorial', true);
      xhr_object.setRequestHeader('Content-Type', 'application/json');
      xhr_object.send();
    }

    document.getElementById('editorTutorial').addEventListener('click', function () {
      MicroModal.show('tutorial_1');
    });

    let btns = document.querySelectorAll('.btn--editor-tutorial');
    for (let btn of btns) {
      btn.addEventListener('click', function () {
        MicroModal.close('tutorial_' + this.dataset.current);
        MicroModal.show('tutorial_' + this.dataset.id);
      });
    }
  }
}
