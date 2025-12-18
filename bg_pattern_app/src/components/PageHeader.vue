<!-- src/components/PageHeader.vue -->
<template>
  <div class="header">
    <h1 class="title">
      <slot name="title">Заголовок страницы</slot>
    </h1>
    <p class="subtitle">
      <slot name="subtitle">Подзаголовок или описание страницы</slot>
    </p>
  </div>
</template>

<script>
export default {
  name: 'PageHeader',
};
</script>

<style scoped>
.bg-primary {
  background-color: #3b82f6;
}
.title{
  font-weight: 700;
  font-style: Bold;
  font-size: 32px;
  line-height: 200%;
  letter-spacing: 0%;
}
.subtitle{
  font-weight: 400;
  font-size: 16px;
  line-height: 200%;
  letter-spacing: 0%;
}
.header{
  margin-bottom: 10px;
}
</style>
