$(function() {
  $('#pagination-short').materializePagination({
      align: 'center',
      lastPage:  3,
      firstPage:  1,
      useUrlParameter: false,
      onClickCallback: function(requestedPage){
          $(".page").hide();
          $("#page-"+requestedPage).show();
      }
  });

  $('#pagination-long').materializePagination({
      align: 'center',
      lastPage:  10,
      firstPage:  1,
      useUrlParameter: false,

  });
});
