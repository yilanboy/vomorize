export interface DueReviewItem {
    group_id: number;
    level_id: number;
    group_title: string;
    words_count: number;
    stage: number;
    target_stage: number;
    badge_text: string;
    badge_variant: string;
    last_score: number | null;
    status_hint: string;
    review_url: string;
}

export interface UpcomingReviewItem {
    group_id: number;
    level_id: number;
    group_title: string;
    stage_label: string;
    next_review_at: string;
    cooldown_remaining_seconds: number;
}

export interface RecommendedGroupItem {
    group_id: number;
    level_id: number;
    group_sequence: number;
    group_title: string;
    words_count: number;
    start_url: string;
}

export interface SrsStageItem {
    key: string;
    label: string;
    groups_count: number;
    words_count: number;
    percentage: number;
    color_class: string;
    description: string;
    is_alert: boolean;
}

export interface LevelMasteryItem {
    id: number;
    name: string;
    subtitle: string;
    description: string;
    mastery_percentage: number;
    mastered_groups_count: number;
    learning_groups_count: number;
    total_groups_count: number;
    total_words_count: number;
    url: string;
}

export interface OverallMasteryItem {
    mastered_groups_count: number;
    total_groups_count: number;
    mastered_words_count: number;
    total_words_count: number;
    mastery_percentage: number;
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
            percentage: number;
        };
        due_groups: {
            count: number;
            has_due: boolean;
        };
    };
    action_center: {
        due_reviews: DueReviewItem[];
        upcoming_reviews: UpcomingReviewItem[];
        recommended_next_group: RecommendedGroupItem | null;
    };
    srs_distribution: {
        total_contacted_groups: number;
        total_contacted_words: number;
        has_data: boolean;
        stages: SrsStageItem[];
    };
    level_progress: {
        levels: LevelMasteryItem[];
        overall: OverallMasteryItem;
    };
}
