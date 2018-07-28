import argparse
import csv
import glob
import json
import logging
import sys

import xmltodict


def extract_verbs(path):
    fileList = glob.glob(path)
    namespace = {'lod':None}
    verb_list = []
    for path in fileList:
        xml = open(path, "r")
        org_xml = xml.read()
        d = xmltodict.parse(org_xml,namespaces=namespace)
        try:
            # if it is a verb
            if 'FLX-VRB' in d['LOD']['ITEM']:
                lod_id = d['LOD']['ITEM']['META']['@ID']
                verb = d['LOD']['ITEM']['FLX-VRB']
                verb_list.append({lod_id:verb})
        except KeyError as e:
            raise e
    return verb_list

def main():
    parser = argparse.ArgumentParser(description='LOD tools.')
    parser.add_argument('cmd', metavar='command', type=str,
                    help='the command you wish to execute')
    parser.add_argument('--path', dest='path', default='lod-dictionary-mirror',
        help='directory where lod XML directory')
    args = parser.parse_args()
    if args.cmd=='extract-verbs':
        search_path = args.path+'/XML/*'
        verb_list = extract_verbs(search_path)
        save_path = args.path+'/verb-conjugations.json'
        with open(save_path,'w') as f:
            json.dump(verb_list,f)
        print("Extracted %d verbs" % len(verb_list))

if __name__ == '__main__':
    main()
