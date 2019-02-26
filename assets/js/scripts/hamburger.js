import $ from 'jquery';

$(window).on('show.bs.collapse hide.bs.collapse', event => {
  $(document.body)
    .toggleClass('menu-active', event.type === 'show');

  $(event.target)
    .parents('.navbar')
    .find('.navbar-toggler')
    .find('.hamburger')
    .toggleClass('is-active', event.type === 'show');
});
