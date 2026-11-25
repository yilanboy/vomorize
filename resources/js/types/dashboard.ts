export interface DueReviewItem {
    group_id: number;
    level_id: number;
    group_title: string;
    stage: number;
    target_stage: number;
    badge_text: string;
    badge_variant: 'warning' | 'success' | 'primary';
    last_score: number | null;
    is_failed: boolean;
    review_url: string;
}

export interface RecommendedGroupItem {
    group_id: number;
    level_id: number;
    group_sequence: number;
    group_title: string;
    start_url: string;
}

export interface DashboardStats {
    is_empty_state: boolean;
    kpis: {
        streak: {
            current: number;
            longest: number;
            is_checked_in_today: boolean;
        };
        learning_words: {
            words_count: number;
            groups_count: number;
        };
        mastered_words: {
            words_count: number;
            groups_count: number;
        };
    };
    action_center: {
        due_reviews: DueReviewItem[];
        recommended_next_group: RecommendedGroupItem | null;
    };
}
