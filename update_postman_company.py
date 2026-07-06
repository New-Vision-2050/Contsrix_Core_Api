import json
import sys
from pathlib import Path


def is_notification(obj):
    if not isinstance(obj, dict):
        return False
    return any(k in obj for k in ('notification_number', 'work_description', 'contractor_name')) and 'company_name' not in obj


def add_company_fields(obj):
    if isinstance(obj, list):
        return [add_company_fields(item) for item in obj]
    if isinstance(obj, dict):
        if is_notification(obj):
            obj['company'] = {'id': 'company-uuid', 'name': 'Company Name'}
            obj['company_name'] = 'Company Name'
        for key, value in obj.items():
            obj[key] = add_company_fields(value)
    return obj


def process_body(body_str):
    try:
        data = json.loads(body_str)
    except Exception:
        return body_str
    return json.dumps(add_company_fields(data), ensure_ascii=False, indent=2)


def walk(node):
    if isinstance(node, dict):
        for key, value in list(node.items()):
            if key == 'response' and isinstance(value, list):
                for resp in value:
                    if isinstance(resp, dict) and isinstance(resp.get('body'), str):
                        resp['body'] = process_body(resp['body'])
            else:
                walk(value)
    elif isinstance(node, list):
        for item in node:
            walk(item)


if __name__ == '__main__':
    path = Path(sys.argv[1])
    collection = json.loads(path.read_text(encoding='utf-8'))
    walk(collection)
    path.write_text(json.dumps(collection, ensure_ascii=False, indent=2), encoding='utf-8')
    print('Updated', path)
