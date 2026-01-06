( function() {
  const { addFilter } = wp.hooks;

  function addAAAReports( reports ) {
    const Redirect = ( target ) => () => {
      window.location = target;
      return null;
    };

    return [
      ...reports,
      {
        report: 'aaa-daily-report',
        title: 'Daily Report (v3)',
        component: Redirect( 'admin.php?page=aaa-daily-report-v3' ),
      },
      {
        report: 'aaa-email-settings',
        title: 'Email Settings',
        component: Redirect( 'admin.php?page=aaa-report-email-settings' ),
      },
    ];
  }

  addFilter(
    'woocommerce_admin_reports_list',
    'aaa-daily-reporting',
    addAAAReports
  );
} )();
