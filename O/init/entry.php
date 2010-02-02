${?PHP}
Header("Content-type: text/html; charset=utf-8");
const O_DOC_ROOT = __DIR__;
require "${ORENA_PATH:'./O':Enter path to Orena framework}/src/EntryPoint.phps";
O_EntryPoint::processRequest();
