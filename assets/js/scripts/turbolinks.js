import turbolinks from 'turbolinks/dist/turbolinks';

if (turbolinks && (!turbolinks.controller || !turbolinks.controller.started)) {
  turbolinks.start();
}
