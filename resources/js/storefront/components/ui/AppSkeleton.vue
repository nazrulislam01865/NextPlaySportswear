<template><div class="np-skeleton"><slot /></div></template>
<style scoped>
.np-skeleton { min-height:180px; background:linear-gradient(90deg,#f2f4f6 25%,#e8edf1 37%,#f2f4f6 63%); background-size:400% 100%; animation:np-shimmer 1.4s ease infinite; }
@keyframes np-shimmer { 0%{background-position:100% 0} 100%{background-position:0 0} }
</style>
