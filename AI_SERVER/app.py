import os
import json
import traceback
from typing import Any, Dict, List

import numpy as np
import pandas as pd
from flask import Flask, request, jsonify
from sklearn.base import BaseEstimator
from joblib import load as joblib_load

try:
    import mysql.connector  # type: ignore
except Exception:
    mysql = None


BASE_DIR = os.path.dirname(os.path.abspath(__file__))
MODELS_DIR = os.path.join(BASE_DIR, 'models')
CONFIG_PATH = os.path.join(BASE_DIR, 'config.json')


def load_config() -> Dict[str, Any]:
    cfg = {
        'mysql': {
            'host': '127.0.0.1',
            'user': 'root',
            'password': '',
            'database': 'hospital_hr_db',
        },
        'port': 8000,
    }
    if os.path.isfile(CONFIG_PATH):
        try:
            with open(CONFIG_PATH, 'r', encoding='utf-8') as f:
                on_disk = json.load(f)
                if isinstance(on_disk, dict):
                    cfg.update(on_disk)
        except Exception:
            pass
    return cfg


class HRModels:
    def __init__(self) -> None:
        self.scaler: Any = None
        self.attrition: BaseEstimator | None = None
        self.pay_reg: BaseEstimator | None = None
        self.iso: BaseEstimator | None = None
        self.promo: BaseEstimator | None = None
        self.role_map: Dict[str, int] = {}
        self.dept_map: Dict[str, int] = {}

    def load(self, models_dir: str) -> None:
        self.scaler = joblib_load(os.path.join(models_dir, 'scaler.joblib'))
        self.attrition = joblib_load(os.path.join(models_dir, 'attrition_lr.joblib'))
        self.pay_reg = joblib_load(os.path.join(models_dir, 'pay_reg.joblib'))
        self.iso = joblib_load(os.path.join(models_dir, 'iso.joblib'))
        self.promo = joblib_load(os.path.join(models_dir, 'promo_lr.joblib'))
        with open(os.path.join(models_dir, 'role_map.json'), 'r', encoding='utf-8') as f:
            self.role_map = json.load(f)
        with open(os.path.join(models_dir, 'dept_map.json'), 'r', encoding='utf-8') as f:
            self.dept_map = json.load(f)

    def build_feature_row(self, rec: Dict[str, Any]) -> List[float]:
        role_idx = float(self.role_map.get(str(rec.get('Role', '')), 0))
        dept_idx = float(self.dept_map.get(str(rec.get('Department', '')), 0))
        return [
            float(rec.get('Age', 0)),
            float(rec.get('Tenure', 0)),
            float(rec.get('BaseMonthlySalary', 0)),
            float(rec.get('OvertimeHours', 0)),
            float(rec.get('AttendanceScore', 0)),
            float(rec.get('PerformanceRating', 0)),
            float(rec.get('HMOClaimsAnnual', 0)),
            float(rec.get('MonthlyHoursWorked', 0)),
            float(rec.get('MonthlyOvertime', 0)),
            float(rec.get('MonthlyLateCount', 0)),
            float(rec.get('MonthlyAbsenceCount', 0)),
            role_idx,
            dept_idx,
        ]

    def predict(self, records: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
        if not records:
            return []
        X = np.array([self.build_feature_row(r) for r in records], dtype=float)
        Xs = self.scaler.transform(X)
        # Attrition probability
        attr_proba = None
        if hasattr(self.attrition, 'predict_proba'):
            attr_proba = self.attrition.predict_proba(Xs)[:, 1]
        elif hasattr(self.attrition, 'predict'):
            # Dummy classifier fallback
            pred = self.attrition.predict(Xs)
            attr_proba = pred.astype(float)
        else:
            attr_proba = np.zeros(Xs.shape[0])
        # Next month net pay
        next_net = self.pay_reg.predict(Xs)
        # Overtime anomaly flag
        anom = (self.iso.predict(Xs) == -1)
        # Promotion prob
        promo_proba = None
        if hasattr(self.promo, 'predict_proba'):
            promo_proba = self.promo.predict_proba(Xs)[:, 1]
        elif hasattr(self.promo, 'predict'):
            promo_proba = self.promo.predict(Xs).astype(float)
        else:
            promo_proba = np.zeros(Xs.shape[0])

        results: List[Dict[str, Any]] = []
        for i, rec in enumerate(records):
            p = float(round(attr_proba[i], 4))
            nxt = float(round(next_net[i], 2))
            af = bool(anom[i])
            pp = float(round(promo_proba[i], 4))
            emp_id = rec.get('EmployeeID') or rec.get('employee_id')
            results.append({
                'employee_id': emp_id,
                'attrition_prob': p,
                'next_month_net_pay': nxt,
                'overtime_anomaly': af,
                'promotion_prob': pp,
                # HR4 aliases
                'attrition_risk': p,
                'next_month_net': nxt,
                'overtime_flag': af,
                'promotion_chance': pp,
            })
        return results


def load_payroll_model(models_dir: str) -> BaseEstimator | None:
    # File name provided by the user
    path = os.path.join(models_dir, 'payroll_anomaly_model.pkl')
    if os.path.isfile(path):
        return joblib_load(path)
    return None


def predict_payroll(model: BaseEstimator | None, records: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    if model is None or not records:
        return []
    df = pd.DataFrame.from_records(records)
    result: List[Dict[str, Any]] = []
    # Try predict_proba → decision_function → predict
    proba = None
    decision = None
    if hasattr(model, 'predict_proba'):
        try:
            proba = model.predict_proba(df)
        except Exception:
            proba = None
    if proba is None and hasattr(model, 'decision_function'):
        try:
            decision = model.decision_function(df)
        except Exception:
            decision = None
    preds = None
    if hasattr(model, 'predict'):
        preds = model.predict(df)

    for i in range(len(df)):
        score = None
        if proba is not None:
            try:
                # binary model assumed; use class 1
                score = float(proba[i, 1])
            except Exception:
                score = None
        if score is None and decision is not None:
            # Map margin to [0,1] via logistic for display
            try:
                margin = float(decision[i])
                score = 1.0 / (1.0 + float(np.exp(-margin)))
            except Exception:
                score = None
        flag = None
        if preds is not None:
            try:
                flag = bool(int(preds[i]) == 1)
            except Exception:
                flag = bool(preds[i])
        result.append({
            'anomaly_flag': bool(flag) if flag is not None else None,
            'anomaly_score': float(round(score, 4)) if score is not None else None,
        })
    return result


def create_app() -> Flask:
    app = Flask(__name__)
    cfg = load_config()

    # Optional DB connectivity (not required for predictions)
    def get_db_conn():
        try:
            if mysql is None:
                return None
            return mysql.connector.connect(
                host=cfg['mysql']['host'],
                user=cfg['mysql']['user'],
                password=cfg['mysql']['password'],
                database=cfg['mysql']['database'],
            )
        except Exception:
            return None

    # Load models once
    hr_models = HRModels()
    hr_models.load(MODELS_DIR)
    payroll_model = load_payroll_model(MODELS_DIR)

    @app.route('/predict/hr', methods=['POST'])
    def predict_hr_route():
        try:
            data = request.get_json(force=True, silent=False) or {}
            records = data.get('records')
            if records is None:
                # Single record fallback
                records = [data]
            if not isinstance(records, list):
                return jsonify({'error': 'records must be a list of objects'}), 400
            results = hr_models.predict(records)
            return jsonify({'items': results})
        except Exception as e:
            return jsonify({'error': 'prediction_failed', 'details': str(e), 'trace': traceback.format_exc()}), 500

    @app.route('/predict/payroll', methods=['POST'])
    def predict_payroll_route():
        try:
            data = request.get_json(force=True, silent=False) or {}
            records = data.get('records')
            if records is None:
                records = [data]
            if not isinstance(records, list):
                return jsonify({'error': 'records must be a list of objects'}), 400
            results = predict_payroll(payroll_model, records)
            return jsonify({'items': results})
        except Exception as e:
            return jsonify({'error': 'prediction_failed', 'details': str(e), 'trace': traceback.format_exc()}), 500

    @app.route('/health', methods=['GET'])
    def health():
        return jsonify({'status': 'ok'})

    return app


if __name__ == '__main__':
    app = create_app()
    cfg = load_config()
    port = int(cfg.get('port', 8000))
    app.run(host='0.0.0.0', port=port)


