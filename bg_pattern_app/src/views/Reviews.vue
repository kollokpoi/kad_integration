<template>
  <section class="reviews-section">
    <h2 class="reviews-title">Отзывы</h2>
    <!-- Сводка отзывов -->
    <div class="reviews-summary">
      <div class="average-rating">
        <span class="rating-value">{{ averageRating.toFixed(1) }}</span>
        <div class="stars">
          <template
            v-for="n in 5"
            :key="n">
            <i :class="n <= Math.round(averageRating) ? 'star filled' : 'star'"></i>
          </template>
        </div>
        <p>На основании {{ reviews.length }} отзывов</p>
      </div>
    </div>
    <!-- Карточки отзывов -->
    <div class="reviews-cards">
      <div
        class="review-card"
        v-for="review in reviews"
        :key="review.id">
        <div class="review-header">
          <img
            v-if="review.avatar"
            :src="review.avatar"
            alt="Avatar"
            class="review-avatar" />
          <div class="reviewer-info">
            <h3 class="reviewer-name">{{ review.name }}</h3>
            <div class="review-rating">
              <template
                v-for="n in 5"
                :key="n">
                <i :class="n <= review.rating ? 'star filled' : 'star'"></i>
              </template>
            </div>
          </div>
        </div>
        <p class="review-text">{{ review.text }}</p>
        <p class="review-date">{{ formatDate(review.date) }}</p>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'Reviews',
  data() {
    return {
      reviews: [
        {
          id: 1,
          name: 'Иван Иванов',
          rating: 5,
          text: 'Отличный сервис! Очень доволен качеством обслуживания и внимательностью команды.',
          date: '2023-11-01',
          avatar: 'https://via.placeholder.com/50',
        },
        {
          id: 2,
          name: 'Мария Петрова',
          rating: 4,
          text: 'Хорошее обслуживание, но есть мелкие недочёты. В целом, рекомендую!',
          date: '2023-10-20',
          avatar: 'https://via.placeholder.com/50',
        },
        {
          id: 3,
          name: 'Алексей Смирнов',
          rating: 5,
          text: 'Потрясающий опыт! Рекомендую всем, кто ценит профессионализм.',
          date: '2023-09-15',
          avatar: 'https://via.placeholder.com/50',
        },
      ],
    };
  },
  computed: {
    averageRating() {
      if (!this.reviews.length) return 0;
      const total = this.reviews.reduce((sum, review) => sum + review.rating, 0);
      return total / this.reviews.length;
    },
  },
  methods: {
    formatDate(dateStr) {
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateStr).toLocaleDateString('ru-RU', options);
    },
  },
};
</script>

<style scoped>
/* Секция отзывов */
.reviews-section {
  padding: 2rem;
  background-color: #fff;
  text-align: center;
}

.reviews-title {
  font-size: 2rem;
  margin-bottom: 1rem;
}

/* Сводка отзывов */
.reviews-summary {
  margin-bottom: 2rem;
}

.average-rating {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.rating-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: #ff9800;
}

.stars {
  display: flex;
  margin: 0.5rem 0;
}

.star {
  font-size: 1.2rem;
  color: #ccc;
}

.star.filled {
  color: #ff9800;
}

/* Сетка карточек отзывов */
.reviews-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  align-items: stretch;
}

/* Карточка отзыва */
.review-card {
  background-color: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1rem;
  text-align: left;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.review-header {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.review-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-right: 1rem;
}

.reviewer-info {
  flex-grow: 1;
}

.reviewer-name {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.review-rating {
  display: flex;
}

.review-text {
  flex-grow: 1;
  margin: 0.5rem 0;
  font-size: 0.95rem;
  color: #333;
}

.review-date {
  font-size: 0.8rem;
  color: #888;
  text-align: right;
  margin-top: auto;
}
</style>
